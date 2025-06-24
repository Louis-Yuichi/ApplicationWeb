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

				$index = [];
				foreach ($header as $j => $colName)
				{
					$index[$colName] = $j;
				}

				foreach ($data as $k => $row)
				{
					if ($k === 0) continue;

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
							$bonusCol = "Bonus $colName";
							$bonus = isset($index[$bonusCol]) ? $row[$index[$bonusCol]] : null;
							if ($bonus === null || $bonus === '') $bonus = 0.00;

							if ($moyenne !== null && $moyenne !== '' && $moyenne !== '~')
							{
								$db->query
								(
									"INSERT INTO \"Competence\" (\"idEtudiant\", \"numeroSemestre\", \"codeCompetence\", \"moyenneCompetence\", \"bonusCompetence\", \"rangCompetence\") VALUES (?, ?, ?, ?, ?, ?)",
									[$idEtudiant, $numeroSemestre, $colName, $moyenne, $bonus, 0]
								);
							}
						}
					}
				}

				$this->calculerTousLesRangs($numeroSemestre);
			}

			return redirect()->to('/scodoc');
		}

		return $this->listeEtudiants();
	}

	public function listeEtudiants()
	{
		$db = db_connect();
		$annee = $this->request->getGet('anneePromotion');
		
		$annees = $db->query("SELECT DISTINCT \"anneePromotion\" FROM \"Etudiant\"
							  ORDER BY \"anneePromotion\"")->getResultArray();

		$etudiants = $annee ? $db->query("SELECT * FROM \"Etudiant\" WHERE \"anneePromotion\" = ?
										  ORDER BY \"nomEtudiant\"", [$annee])->getResultArray() : [];
		
		return $this->view('scodoc/scodoc.html.twig',
		[
			'etudiants' => $etudiants,
			'anneePromotion' => $annee,
			'annees' => array_column($annees, 'anneePromotion')
		]);
	}

	public function etudiantsParAnnee($annee)
	{
		$db = db_connect();
		$etudiants = $db->query(" SELECT * FROM \"Etudiant\"
								  WHERE \"anneePromotion\" = ? AND \"parcoursEtudes\" LIKE '%S6'
								  ORDER BY \"nomEtudiant\" ", [$annee])->getResultArray();

		return $this->response->setJSON($etudiants);
	}

	public function absencesParEtudiant($idEtudiant)
	{
		$db = db_connect();

		$result = $db->query(" SELECT
							   COALESCE(SUM(CASE WHEN \"numeroSemestre\" IN (1,2) THEN \"nbAbsencesInjust\" END), '') as but1,
							   COALESCE(SUM(CASE WHEN \"numeroSemestre\" IN (3,4) THEN \"nbAbsencesInjust\" END), '') as but2,
							   COALESCE(SUM(CASE WHEN \"numeroSemestre\" IN (5,6) THEN \"nbAbsencesInjust\" END), '') as but3
							   FROM \"Semestre\" WHERE \"idEtudiant\" = ? ", [$idEtudiant])->getRow();
		
		return $this->response->setJSON($result);
	}

	public function apprentissageParEtudiant($idEtudiant)
	{
		$db = db_connect();

		$result = $db->query(" SELECT 
							   COALESCE(MAX(CASE WHEN \"numeroSemestre\" IN (1,2) THEN \"apprentissage\" END), '') as but1,
							   COALESCE(MAX(CASE WHEN \"numeroSemestre\" IN (3,4) THEN \"apprentissage\" END), '') as but2,
							   COALESCE(MAX(CASE WHEN \"numeroSemestre\" IN (5,6) THEN \"apprentissage\" END), '') as but3
							   FROM \"Semestre\" WHERE \"idEtudiant\" = ? ", [$idEtudiant])->getRow();

		return $this->response->setJSON($result);
	}

	private function calculerTousLesRangs($numeroSemestre)
	{
		$db = db_connect();

		$db->query(" UPDATE \"Competence\" SET \"rangCompetence\" = (
					 SELECT COUNT(*) + 1 FROM \"Competence\" c2 WHERE c2.\"numeroSemestre\" = \"Competence\".\"numeroSemestre\"
					 AND c2.\"codeCompetence\" = \"Competence\".\"codeCompetence\"
					 AND c2.\"moyenneCompetence\" > \"Competence\".\"moyenneCompetence\")
					 WHERE \"numeroSemestre\" = ? ", [$numeroSemestre]);
	}
}