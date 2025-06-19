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

					$nbAbsences     = $row[$index['Abs']] - $row[$index['Just.']];

					$db = db_connect();
					$db->query
					(
						"INSERT INTO \"Etudiant\" (\"idEtudiant\", \"nomEtudiant\", \"prenomEtudiant\", \"parcoursEtudes\", \"anneePromotion\") VALUES (?, ?, ?, ?, ?)",
						[$idEtudiant, $nomEtudiant, $prenomEtudiant, $parcoursEtudes, $anneePromotion]
					);

					$db->query
					(
						"INSERT INTO \"Semestre\" (\"idEtudiant\", \"numeroSemestre\", \"nbAbsences\") VALUES (?, ?, ?)",
						[$idEtudiant, $numeroSemestre, $nbAbsences]
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
									"INSERT INTO \"Competence\" (\"idEtudiant\", \"numeroSemestre\", \"codeCompetence\", \"nomCompetence\", \"moyenneCompetence\", \"bonusCompetence\", \"rangCompetence\") VALUES (?, ?, ?, ?, ?, ?, ?)",
									[$idEtudiant, $numeroSemestre, $colName, $colName, $moyenne, $bonus, 99]
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
			->select('numeroSemestre, nbAbsences')
			->where('idEtudiant', $idEtudiant)
			->get()
			->getResultArray();

		// Regroupe par BUT
		$but1 = 0; $but2 = 0; $but3 = 0;
		foreach ($absences as $abs)
		{
			if (in_array($abs['numeroSemestre'], [1,2])) $but1 += $abs['nbAbsences'];
			if (in_array($abs['numeroSemestre'], [3,4])) $but2 += $abs['nbAbsences'];
			if (in_array($abs['numeroSemestre'], [5,6])) $but3 += $abs['nbAbsences'];
		}
		return $this->response->setJSON
		([
			'but1' => $but1,
			'but2' => $but2,
			'but3' => $but3
		]);
	}
}