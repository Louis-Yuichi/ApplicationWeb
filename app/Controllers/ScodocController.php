<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ScodocController extends BaseController
{
	public function menu()
	{
		$this->view('scodoc/scodoc.html.twig');
	}

	public function importer()
	{
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichier']) && isset($_POST['annee'])) {
			$file = $_FILES['fichier']['tmp_name'];
			$annee = $_POST['annee'];

			// Lecture du fichier Excel
			$spreadsheet = IOFactory::load($file);
			$sheet = $spreadsheet->getActiveSheet();
			$header = $sheet->rangeToArray('A1:Z1')[0];

			$colonnesAttendues = [
				"Nom", "Prénom", "BUT1", "BUT2", "BUT3", "Parcours", "Absences"
			];

			foreach ($colonnesAttendues as $col) {
				if (!in_array($col, $header)) {
					die("Fichier non conforme : colonne '$col' manquante.");
				}
			}

			$data = $sheet->toArray();
			unset($data[0]); // On retire l'en-tête

			$db = db_connect();
			foreach ($data as $row) {
				$db->query(
					"INSERT INTO scodoc (nom, prenom, but1, but2, but3, parcours, absences, annee) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
					[$row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $annee]
				);
			}

			echo "Import réussi !";
		} else {
			echo "Aucun fichier ou année reçus.";
		}
	}
}