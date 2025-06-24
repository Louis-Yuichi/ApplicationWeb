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
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichier'], $_POST['anneePromotion']))
		{
			$fichiers       = $_FILES['fichier'];
			$anneePromotion = $_POST['anneePromotion'];

			for ($i = 0; $i < count($fichiers['name']); $i++)
			{
				$this->traiterFichier($fichiers, $i, $anneePromotion);
			}
		}

		return $this->listeEtudiants();
	}

	private function traiterFichier($fichiers, $i, $anneePromotion)
	{
		$spreadsheet    = IOFactory::load($fichiers['tmp_name'][$i]);
		$data           = $spreadsheet->getActiveSheet()->toArray(null, true, false);
		$numeroSemestre = intval($fichiers['name'][$i][1] ?? 0);
		$indexColonnes  = array_flip($data[0]);

		foreach (array_slice($data, 1) as $ligne)
		{
			if (empty($ligne[$indexColonnes['etudid']])) break;

			$this->traiterEtudiant($ligne, $indexColonnes, $numeroSemestre, $anneePromotion);
		}

		$this->calculerTousLesRangs($numeroSemestre);
	}

	private function traiterEtudiant($ligne, $indexColonnes, $numeroSemestre, $anneePromotion)
	{
		$db = db_connect();
		
		$idEtudiant       = $ligne[$indexColonnes['etudid']];
		$nomEtudiant      = $ligne[$indexColonnes['Nom'   ]];
		$prenomEtudiant   = $ligne[$indexColonnes['Prénom']];
		$parcoursEtudes   = $ligne[$indexColonnes['Cursus']];
		$nbAbsencesInjust = $ligne[$indexColonnes['Abs'   ]] - $ligne[$indexColonnes['Just.']];

		$db->query('INSERT INTO "Etudiant" ("idEtudiant", "nomEtudiant", "prenomEtudiant", "parcoursEtudes", "anneePromotion")
					VALUES (?, ?, ?, ?, ?) ON CONFLICT ("idEtudiant") DO NOTHING',
					[$idEtudiant, $nomEtudiant, $prenomEtudiant, $parcoursEtudes, $anneePromotion]);

		$db->query('INSERT INTO "Semestre" ("idEtudiant", "numeroSemestre", "nbAbsencesInjust") 
					VALUES (?, ?, ?) ON CONFLICT ("idEtudiant", "numeroSemestre") DO UPDATE SET "nbAbsencesInjust" = ?',
					[$idEtudiant, $numeroSemestre, $nbAbsencesInjust, $nbAbsencesInjust]);

		foreach ($indexColonnes as $nomColonne => $j)
		{
			if (preg_match('/^BIN\d+$/', $nomColonne) && !empty($ligne[$j]) && $ligne[$j] !== '~')
			{
				$moyenne = $ligne[$j];
				$bonus   = $ligne[$indexColonnes["Bonus $nomColonne"] ?? null] ?? 0.00;

				$db->query('INSERT INTO "Competence" ("idEtudiant", "numeroSemestre", "codeCompetence", "moyenneCompetence", "bonusCompetence", "rangCompetence")
							VALUES (?, ?, ?, ?, ?, 0) ON CONFLICT("idEtudiant", "numeroSemestre", "codeCompetence") DO UPDATE SET "moyenneCompetence" = ?, "bonusCompetence" = ?',
							[$idEtudiant, $numeroSemestre, $nomColonne, $moyenne, $bonus, $moyenne, $bonus]);
			}
		}
	}

	public function listeEtudiants()
	{
		$db = db_connect();

		$annee = $this->request->getGet('anneePromotion');

		$annees    = $db->query('SELECT DISTINCT "anneePromotion" FROM "Etudiant"
								 ORDER BY "anneePromotion"')->getResultArray();

		$etudiants = $annee ? $db->query('SELECT * FROM "Etudiant" WHERE "anneePromotion" = ?
										  ORDER BY "nomEtudiant"', [$annee])->getResultArray() : [];

		return $this->view('scodoc/scodoc.html.twig',
		[
			'etudiants'      => $etudiants,
			'anneePromotion' => $annee,
			'annees'         => array_column($annees, 'anneePromotion')
		]);
	}

	public function etudiantsParAnnee($annee)
	{
		$db = db_connect();

		$etudiants = $db->query('SELECT * FROM "Etudiant" WHERE "anneePromotion" = ? AND "parcoursEtudes"
								LIKE \'%S6\' ORDER BY "nomEtudiant"', [$annee])->getResultArray();

		return $this->response->setJSON($etudiants);
	}

	public function absencesParEtudiant($idEtudiant)
	{
		$db = db_connect();

		$resultat = $db->query('SELECT
								COALESCE(SUM(CASE WHEN "numeroSemestre" IN (1,2) THEN "nbAbsencesInjust" END), 0) as but1,
								COALESCE(SUM(CASE WHEN "numeroSemestre" IN (3,4) THEN "nbAbsencesInjust" END), 0) as but2,
								COALESCE(SUM(CASE WHEN "numeroSemestre" IN (5,6) THEN "nbAbsencesInjust" END), 0) as but3
								FROM "Semestre" WHERE "idEtudiant" = ?', [$idEtudiant])->getRow();

		return $this->response->setJSON
		([
			'but1' => $resultat->but1 == 0 ? '' : $resultat->but1,
			'but2' => $resultat->but2 == 0 ? '' : $resultat->but2,
			'but3' => $resultat->but3 == 0 ? '' : $resultat->but3
		]);
	}

	public function apprentissageParEtudiant($idEtudiant)
	{
		$db = db_connect();

		$resultat = $db->query('SELECT
								COALESCE(MAX(CASE WHEN "numeroSemestre" IN (1,2) THEN "apprentissage" END), \'\') as but1,
								COALESCE(MAX(CASE WHEN "numeroSemestre" IN (3,4) THEN "apprentissage" END), \'\') as but2,
								COALESCE(MAX(CASE WHEN "numeroSemestre" IN (5,6) THEN "apprentissage" END), \'\') as but3
								FROM "Semestre" WHERE "idEtudiant" = ?', [$idEtudiant])->getRow();

		return $this->response->setJSON($resultat);
	}

	private function calculerTousLesRangs($numeroSemestre)
	{
		$db = db_connect();

		$db->query('UPDATE "Competence" SET "rangCompetence" = (
					SELECT COUNT(*) + 1 FROM "Competence" c2
					WHERE  c2."numeroSemestre"    = "Competence"."numeroSemestre"
					AND    c2."codeCompetence"    = "Competence"."codeCompetence"
					AND    c2."moyenneCompetence" > "Competence"."moyenneCompetence")
					WHERE  "numeroSemestre"       = ?', [$numeroSemestre]);
	}
}