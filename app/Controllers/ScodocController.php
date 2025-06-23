<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ScodocController extends BaseController
{
	public function menu()
	{
		$this->view('scodoc/scodoc.html.twig');
	}

	public function importerScodoc()
	{
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichier']) && isset($_POST['anneePromotion']))
		{
			$anneePromotion = $_POST['anneePromotion'];
			$files = $_FILES['fichier'];

			// Gère l'import de plusieurs fichiers
			for ($i = 0; $i < count($files['name']); $i++)
			{
				$file = $files['tmp_name'][$i];
				$filename = $files['name'][$i];

				$numeroSemestre = isset($filename[1]) ? intval($filename[1]) : null;

				$spreadsheet = IOFactory::load($file);

				$sheet         = $spreadsheet->getActiveSheet();
				$highestRow    = $sheet->getHighestRow();
				$highestColumn = $sheet->getHighestColumn();
				$data          = $sheet->rangeToArray("A1:{$highestColumn}{$highestRow}", null, true, false);

				$header = $data[0];

				// Associe chaque nom de colonne à son index
				$index = [];
				foreach ($header as $j => $colName)
				{
					$index[$colName] = $j;
				}

				foreach ($data as $k => $row)
				{
					if ($k === 0) continue; // saute l'en-tête

					if (empty($row[$index['etudid']])) break;

					$idEtudiant     = $row[$index['etudid']];
					$nomEtudiant    = $row[$index['Nom']];
					$prenomEtudiant = $row[$index['Prénom']];
					$parcoursEtudes = $row[$index['Cursus']];

					$nbAbsencesInjust = $row[$index['Abs']] - $row[$index['Just.']];

					$db = db_connect();
					$db->query
					(
						"INSERT INTO \"Etudiant\" (\"idEtudiant\", \"nomEtudiant\", \"prenomEtudiant\", \"parcoursEtudes\", \"anneePromotion\") VALUES (?, ?, ?, ?, ?)",
						[$idEtudiant, $nomEtudiant, $prenomEtudiant, $parcoursEtudes, $anneePromotion]
					);

					$db->query
					(
						"INSERT INTO \"Semestre\" (\"idEtudiant\", \"numeroSemestre\", \"nbAbsencesInjust\") VALUES (?, ?, ?)",
						[$idEtudiant, $numeroSemestre, $nbAbsencesInjust]
					);

					foreach ($index as $colName => $j)
					{
						if (preg_match('/^BIN\d+$/', $colName))
						{
							$moyenne = $row[$j];
							// Bonus éventuel (ex: "Bonus BIN11")
							$bonusCol = "Bonus $colName";
							$bonus = isset($index[$bonusCol]) ? $row[$index[$bonusCol]] : null;
							if ($bonus === null || $bonus === '') $bonus = 0.00;

							// On ne stocke que si la case n'est pas vide ou ~
							if ($moyenne !== null && $moyenne !== '' && $moyenne !== '~')
							{
								$db->query
								(
									"INSERT INTO \"Competence\" (\"idEtudiant\", \"numeroSemestre\", \"codeCompetence\", \"moyenneCompetence\", \"bonusCompetence\", \"rangCompetence\") VALUES (?, ?, ?, ?, ?, ?)",
									[$idEtudiant, $numeroSemestre, $colName, $moyenne, $bonus, 1]
								);
							}
						}
					}
				}
			}
			// Redirige vers la page de la liste après l'import
			return redirect()->to('/scodoc');
		}
	}

	public function listeEtudiants()
	{
		$db = db_connect();

		$annees = $db->table('Etudiant')
			->select('anneePromotion')
			->distinct()
			->orderBy('anneePromotion', 'ASC')
			->get()
			->getResultArray();
		$annees = array_column($annees, 'anneePromotion');

		$annee  = $this->request->getGet('anneePromotion');

		$etudiants = [];
		if ($annee)
		{
			$etudiants = $db->table('Etudiant')
				->where('anneePromotion', $annee)
				->orderBy('nomEtudiant', 'ASC')
				->get()
				->getResultArray();
		}

		return $this->view('scodoc/scodoc.html.twig',
		[
			'etudiants' => $etudiants,
			'anneePromotion' => $annee,
			'annees' => $annees
		]);
	}

	public function etudiantsParAnnee($annee)
	{
		$db = db_connect();
		$etudiants = $db->table('Etudiant')
			->where('anneePromotion', $annee)
			->like('parcoursEtudes', 'S6', 'right')
			->orderBy('nomEtudiant', 'ASC')
			->get()
			->getResultArray();
		return $this->response->setJSON($etudiants);
	}

	public function absencesParEtudiant($idEtudiant)
	{
		$db = db_connect();
		$absences = $db->table('Semestre')
			->select('numeroSemestre, nbAbsencesInjust')
			->where('idEtudiant', $idEtudiant)
			->get()
			->getResultArray();

		// Initialisé à null pour détecter l'absence de données
		$but = [1 => null, 2 => null, 3 => null];
		
		foreach ($absences as $abs) {
			if (in_array($abs['numeroSemestre'], [1,2])) {
				$but[1] = ($but[1] ?? 0) + $abs['nbAbsencesInjust'];
			}
			if (in_array($abs['numeroSemestre'], [3,4])) {
				$but[2] = ($but[2] ?? 0) + $abs['nbAbsencesInjust'];
			}
			if (in_array($abs['numeroSemestre'], [5,6])) {
				$but[3] = ($but[3] ?? 0) + $abs['nbAbsencesInjust'];
			}
		}
		
		return $this->response->setJSON([
			'but1' => $but[1] ?? '', // Affiche vide si aucun semestre S1/S2
			'but2' => $but[2] ?? '', // Affiche vide si aucun semestre S3/S4
			'but3' => $but[3] ?? ''  // Affiche vide si aucun semestre S5/S6
		]);
	}

	public function apprentissageParEtudiant($idEtudiant)
	{
    $db = db_connect();
    $semestres = $db->table('Semestre')
        ->select('numeroSemestre, apprentissage')
        ->where('idEtudiant', $idEtudiant)
        ->get()
        ->getResultArray();

    $but = [1 => null, 2 => null, 3 => null];
    foreach ($semestres as $s) {
        if (in_array($s['numeroSemestre'], [1, 2]) && $but[1] === null) {
            $but[1] = $s['apprentissage'];
        }
        if (in_array($s['numeroSemestre'], [3, 4]) && $but[2] === null) {
            $but[2] = $s['apprentissage'];
        }
        if (in_array($s['numeroSemestre'], [5, 6]) && $but[3] === null) {
            $but[3] = $s['apprentissage'];
        }
    }

    return $this->response->setJSON([
        'but1' => $but[1] ?? '',
        'but2' => $but[2] ?? '',
        'but3' => $but[3] ?? ''
    ]);
}


}