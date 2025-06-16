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
			$file = $_FILES['fichier']['tmp_name'];
			$anneePromotion = $_POST['anneePromotion'];

			// Récupérer le nom du fichier original
			$filename = $_FILES['fichier']['name'];

			preg_match('/^(S\d+)/', $filename, $matches);
			$numeroSemestre = isset($matches[1]) ? $matches[1] : null;

			// Lecture du fichier Excel
			$spreadsheet = IOFactory::load($file);
			$sheet = $spreadsheet->getActiveSheet();
			$data = $sheet->rangeToArray('A1:AW50', null, true, false);

			$header = $data[0];

			$colonnesAttendues =
			[
				'etudid', 'code_nip', 'Rg', 'Nom', 'Civ.', 'Nom', 'Prénom', 'Parcours', 'TD', 'TP', 'Cursus',
				'UEs', 'Moy', 'Abs', 'Just.'
			];

			foreach ($colonnesAttendues as $col)
			{
				if (!in_array($col, $header))
				{
					die("Fichier non conforme : colonne '$col' manquante.");
				}
			}

			unset($data[0]); // On retire l'en-tête

			$db = db_connect();
			foreach ($data as $row)
			{
				// Si la première colonne (Nom) est vide, on arrête la lecture
				if (empty($row[0]))
				{
					break;
				}

				$db->query
				(
					"INSERT INTO \"Etudiant\" (\"idEtudiant\", \"nomEtudiant\", \"prenomEtudiant\", \"parcoursEtudes\", \"anneePromotion\") VALUES (?, ?, ?, ?, ?)",
					[$row[0], $row[5], $row[6], $row[10], $anneePromotion]
				);
			}

			echo "Import réussi !";
		}
		else
		{
			echo "Aucun fichier ou année reçus.";
		}
	}
}