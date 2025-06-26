<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ScodocController extends BaseController
{
	public function index()
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

		// Debug: afficher les colonnes disponibles
		if ($idEtudiant == '8820') { // Remplacez par un ID test
			error_log("Semestre $numeroSemestre - Colonnes disponibles: " . implode(', ', array_keys($indexColonnes)));
		}

		// Définir les ressources par semestre (maths et anglais)
		$ressourcesVoulues = [
			1 => ['BINR106', 'BINR107', 'BINR110'],
			2 => ['BINR207', 'BINR208', 'BINR209', 'BINR212'],
			3 => ['BINR308', 'BINR309', 'BINR312'],
			4 => ['BINR403', 'BINR404', 'BINR412'],
			5 => ['BINR504', 'BINR511', 'BINR512']
		];

		foreach ($indexColonnes as $nomColonne => $j)
		{
			// Traitement des compétences
			if (preg_match('/^BIN\d+$/', $nomColonne) && !empty($ligne[$j]) && $ligne[$j] !== '~')
			{
				$moyenne = $ligne[$j];

				$db->query('INSERT INTO "Competence" ("idEtudiant", "numeroSemestre", "codeCompetence", "moyenneCompetence")
							VALUES (?, ?, ?, ?) ON CONFLICT("idEtudiant", "numeroSemestre", "codeCompetence") DO UPDATE SET "moyenneCompetence" = ?',
							[$idEtudiant, $numeroSemestre, $nomColonne, $moyenne, $moyenne]);
			}

			// Traitement des ressources (maths et anglais seulement)
			if (isset($ressourcesVoulues[$numeroSemestre]) && 
				in_array($nomColonne, $ressourcesVoulues[$numeroSemestre]) && 
				!empty($ligne[$j]) && $ligne[$j] !== '~')
			{
				$moyenne = $ligne[$j];

				// Debug: confirmer l'insertion
				error_log("Ressource trouvée: $nomColonne = $moyenne pour étudiant $idEtudiant, semestre $numeroSemestre");

				$db->query('INSERT INTO "Ressource" ("idEtudiant", "numeroSemestre", "codeRessource", "moyenneRessource")
							VALUES (?, ?, ?, ?) ON CONFLICT("idEtudiant", "numeroSemestre", "codeRessource") DO UPDATE SET "moyenneRessource" = ?',
							[$idEtudiant, $numeroSemestre, $nomColonne, $moyenne, $moyenne]);
			}
		}
	}

	public function etudiantsParAnnee($annee)
	{
		$db = db_connect();

		$etudiants = $db->query('SELECT DISTINCT e.* FROM "Etudiant" e 
								 INNER JOIN "Semestre" s ON e."idEtudiant" = s."idEtudiant" 
								 WHERE e."anneePromotion" = ? AND s."numeroSemestre" IN (5,6)
								 ORDER BY e."nomEtudiant"', [$annee])->getResultArray();

		return $this->response->setJSON($etudiants);
	}

	public function absencesParEtudiant($idEtudiant)
	{
		$db = db_connect();

		$resultat = $db->query('SELECT
							COALESCE(SUM(CASE WHEN "numeroSemestre" IN (1,2) THEN "nbAbsencesInjust" END), 0) as but1,
							COALESCE(SUM(CASE WHEN "numeroSemestre" IN (3,4) THEN "nbAbsencesInjust" END), 0) as but2,
							COALESCE(SUM(CASE WHEN "numeroSemestre" IN (  5) THEN "nbAbsencesInjust" END), 0) as but3
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
								COALESCE(MAX(CASE WHEN "numeroSemestre" IN (  5) THEN "apprentissage" END), \'\') as but3
								FROM "Semestre" WHERE "idEtudiant" = ?', [$idEtudiant])->getRow();

		return $this->response->setJSON($resultat);
	}

	public function competencesParEtudiant($idEtudiant)
	{
		$db = db_connect();

		$resultat = $db->query('SELECT "numeroSemestre", "codeCompetence", "moyenneCompetence",
							  ( SELECT COUNT(*) + 1 FROM "Competence" c2 WHERE c2."numeroSemestre" = c1."numeroSemestre"
								AND c2."codeCompetence" = c1."codeCompetence"
								AND CAST(c2."moyenneCompetence" AS FLOAT) > CAST(c1."moyenneCompetence" AS FLOAT)
							  ) as "rangCompetence" FROM "Competence" c1 WHERE c1."idEtudiant" = ? 
								ORDER BY c1."numeroSemestre", c1."codeCompetence"', [$idEtudiant])->getResultArray();

		return $this->response->setJSON($resultat);
	}

	public function ressourcesParEtudiant($idEtudiant)
	{
		$db = db_connect();

		$resultat = $db->query('SELECT "numeroSemestre", "codeRessource", "moyenneRessource",
						  ( SELECT COUNT(*) + 1 FROM "Ressource" r2 WHERE r2."numeroSemestre" = r1."numeroSemestre"
							AND r2."codeRessource" = r1."codeRessource"
							AND CAST(r2."moyenneRessource" AS FLOAT) > CAST(r1."moyenneRessource" AS FLOAT)
						  ) as "rangRessource" FROM "Ressource" r1 WHERE r1."idEtudiant" = ? 
							ORDER BY r1."numeroSemestre", r1."codeRessource"', [$idEtudiant])->getResultArray();

		return $this->response->setJSON($resultat);
	}

	public function sauvegarderAvis()
	{
		if (!$this->request->isAJAX()) {
			return $this->response->setJSON(['success' => false, 'message' => 'Requête non autorisée']);
		}
		
		$json = $this->request->getJSON(true);
		
		if (!isset($json['idEtudiant'], $json['typePoursuite'], $json['typeAvis'])) {
			return $this->response->setJSON(['success' => false, 'message' => 'Données manquantes']);
		}
		
		$db = db_connect();
		
		try {
			// Utiliser votre structure existante
			$db->query('INSERT INTO "Avis" ("idEtudiant", "typePoursuite", "typeAvis") 
						VALUES (?, ?, ?) 
						ON CONFLICT ("idEtudiant", "typePoursuite") 
						DO UPDATE SET "typeAvis" = ?',
				[$json['idEtudiant'], $json['typePoursuite'], $json['typeAvis'], $json['typeAvis']]);
			
			return $this->response->setJSON(['success' => true, 'message' => 'Avis sauvegardé']);
			
		} catch (\Exception $e) {
			return $this->response->setJSON(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
		}
	}

	public function sauvegarderCommentaire()
	{
		if (!$this->request->isAJAX()) {
			return $this->response->setJSON(['success' => false, 'message' => 'Requête non autorisée']);
		}
		
		$json = $this->request->getJSON(true);
		
		if (!isset($json['idEtudiant'], $json['commentaire'])) {
			return $this->response->setJSON(['success' => false, 'message' => 'Données manquantes']);
		}
		
		$db = db_connect();
		
		try {
			// Sauvegarder le commentaire avec un type spécial
			$db->query('INSERT INTO "Avis" ("idEtudiant", "typePoursuite", "typeAvis", "commentaire") 
						VALUES (?, ?, ?, ?) 
						ON CONFLICT ("idEtudiant", "typePoursuite") 
						DO UPDATE SET "commentaire" = ?',
				[$json['idEtudiant'], 'commentaire_general', 'sans_avis', $json['commentaire'], $json['commentaire']]);
			
			return $this->response->setJSON(['success' => true, 'message' => 'Commentaire sauvegardé']);
			
		} catch (\Exception $e) {
			return $this->response->setJSON(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
		}
	}

	public function avisParEtudiant($idEtudiant)
	{
		$db = db_connect();
		
		$resultat = $db->query('SELECT "typePoursuite", "typeAvis", "commentaire" FROM "Avis" WHERE "idEtudiant" = ?', 
						 [$idEtudiant])->getResultArray();
		
		// Convertir en format plus pratique
		$avis = [];
		foreach ($resultat as $row) {
			if ($row['typePoursuite'] === 'commentaire_general') {
				$avis['commentaire'] = $row['commentaire'];
			} else {
				$avis[$row['typePoursuite']] = $row['typeAvis'];
			}
		}
		
		return $this->response->setJSON($avis);
	}

	public function statistiquesAvisPromotion($anneePromotion)
	{
		$db = db_connect();
		
		$resultat = $db->query('SELECT a."typePoursuite", a."typeAvis", COUNT(*) as "nombre"
							   FROM "Avis" a
							   INNER JOIN "Etudiant" e ON a."idEtudiant" = e."idEtudiant"
							   WHERE e."anneePromotion" = ? AND a."typePoursuite" IN (?, ?)
							   GROUP BY a."typePoursuite", a."typeAvis"', 
							  [$anneePromotion, 'ecole_ingenieur', 'master'])->getResultArray();
		
		// Organiser les données
		$stats = [
			'ecole_ingenieur' => [
				'tres_favorable' => 0, 'favorable' => 0, 'assez_favorable' => 0, 
				'sans_avis' => 0, 'reserve' => 0
			],
			'master' => [
				'tres_favorable' => 0, 'favorable' => 0, 'assez_favorable' => 0, 
				'sans_avis' => 0, 'reserve' => 0
			]
		];
		
		foreach ($resultat as $row) {
			if (isset($stats[$row['typePoursuite']][$row['typeAvis']])) {
				$stats[$row['typePoursuite']][$row['typeAvis']] = intval($row['nombre']);
			}
		}
		
		return $this->response->setJSON($stats);
	}

	public function modifierEtudiant()
	{
		if (!$this->request->isAJAX()) {
			return $this->response->setJSON(['success' => false, 'message' => 'Requête non autorisée']);
		}
		
		$json = $this->request->getJSON(true);
		log_message('info', 'Données reçues pour modification: ' . json_encode($json));
		
		if (!isset($json['idEtudiant'])) {
			return $this->response->setJSON(['success' => false, 'message' => 'ID étudiant manquant']);
		}
		
		$db = db_connect();
		
		try {
			// Mettre à jour la mobilité dans la table Etudiant
			if (isset($json['mobilite_etranger'])) {
				log_message('info', 'Mise à jour mobilité: ' . $json['mobilite_etranger']);
				$db->query('UPDATE "Etudiant" SET "mobiliteEtranger" = ? WHERE "idEtudiant" = ?',
						  [$json['mobilite_etranger'], $json['idEtudiant']]);
			}
			
			// Mettre à jour seulement l'apprentissage BUT 3 (semestre 5)
			if (isset($json['apprentissage_but3'])) {
				log_message('info', 'Mise à jour apprentissage BUT3: ' . $json['apprentissage_but3']);
				$db->query('UPDATE "Semestre" SET "apprentissage" = ? WHERE "idEtudiant" = ? AND "numeroSemestre" = 5',
						  [$json['apprentissage_but3'], $json['idEtudiant']]);
			}
			
			return $this->response->setJSON(['success' => true, 'message' => 'Données mises à jour']);
			
		} catch (\Exception $e) {
			log_message('error', 'Erreur modifierEtudiant: ' . $e->getMessage());
			return $this->response->setJSON(['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
		}
	}

	public function importerDonnees()
	{
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichier']))
		{
			$fichiers = $_FILES['fichier'];

			for ($i = 0; $i < count($fichiers['name']); $i++)
			{
				$spreadsheet = IOFactory::load($fichiers['tmp_name'][$i]);
				$donnees     = $spreadsheet->getActiveSheet()->toArray(null, true, false);

				// Vérification de la structure
				$indexColonnes = array_flip($donnees[0]);
				if (!isset($indexColonnes['etudid'], $indexColonnes['Nom'], $indexColonnes['Prénom'], $indexColonnes['Cursus'])) {
					return $this->response->setJSON(['success' => false, 'message' => 'Fichier invalide (structure)']);
				}

				// Lors du traitement des données, filtrer les semestres
				foreach ($donnees as $ligne) {
					$numeroSemestre = intval($ligne['numeroSemestre']);
					
					// Ignorer les semestres supérieurs à 5
					if ($numeroSemestre > 5) {
						log_message('info', 'Semestre ' . $numeroSemestre . ' ignoré (supérieur à 5)');
						continue;
					}
					
					// Traiter seulement les semestres 1 à 5
					$this->traiterEtudiant($ligne, $indexColonnes, $numeroSemestre, $anneePromotion);
				}
			}
		}

		return $this->response->setJSON(['success' => true, 'message' => 'Importation terminée']);
	}
}