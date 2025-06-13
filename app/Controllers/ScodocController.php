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
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichier']) && isset($_POST['annee']))
		{
			$file = $_FILES['fichier']['tmp_name'];
			$annee = $_POST['annee'];

			// Lecture du fichier Excel
			$spreadsheet = IOFactory::load($file);
			$sheet = $spreadsheet->getActiveSheet();
			$data = $sheet->rangeToArray('A1:AW50', null, true, false);

			// Affichage temporaire pour test
			echo '<pre>';
			var_dump($data);
			echo '</pre>';
			exit;

			$header = $data[0];

			$colonnesAttendues =
			[
				'etudid', 'code_nip', 'Rg', 'Nom', 'Civ.', 'Nom', 'Prénom', 'Parcours', 'TD', 'TP', 'Cursus',
				'UEs', 'Moy', 'Abs', 'Just.', 'BIN51', 'Bonus BIN51', 'BINR504', 'BINR505', 'BINR506', 'BINR507',
				'BINR508', 'BINR509', 'BINR510', 'BINR513', 'BINR514', 'BINS501', 'BIN52', 'Bonus BIN52', 'BINR504',
				'BINR505', 'BINR506', 'BINR508', 'BINR509', 'BINR510', 'BINR511', 'BINR512', 'BINR514', 'BINS501', 'BIN56',
				'Bonus BIN56', 'BINR501', 'BINR502', 'BINR503', 'BINR506', 'BINR507', 'BINR513', 'BINR514', 'BINS501'
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
					"INSERT INTO scodoc (nom, prenom, but1, but2, but3, parcours, absences, annee) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
					[$row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $annee]
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