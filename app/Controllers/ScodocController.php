<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ScodocController extends BaseController
{
    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichier'], $_POST['anneePromotion']))
        {
            $fichiers = $_FILES['fichier'];
            $anneePromotion = $_POST['anneePromotion'];

            for ($i = 0; $i < count($fichiers['name']); $i++)
            {
                $this->traiterFichier($fichiers, $i, $anneePromotion);
            }
        }

        $db = db_connect();
        $annee = $this->request->getGet('anneePromotion');

        $annees = $db->query('SELECT DISTINCT "anneePromotion" FROM "Etudiant" ORDER BY "anneePromotion"')->getResultArray();
        $etudiants = $annee ? $db->query('SELECT * FROM "Etudiant" WHERE "anneePromotion" = ? ORDER BY "nomEtudiant"', [$annee])->getResultArray() : [];

        return $this->view('scodoc/scodoc.html.twig', [
            'etudiants' => $etudiants,
            'anneePromotion' => $annee,
            'annees' => array_column($annees, 'anneePromotion')
        ]);
    }

    private function traiterFichier($fichiers, $i, $anneePromotion)
    {
        $spreadsheet = IOFactory::load($fichiers['tmp_name'][$i]);
        $data = $spreadsheet->getActiveSheet()->toArray(null, true, false);
        $numeroSemestre = intval($fichiers['name'][$i][1] ?? 0);
        $indexColonnes = array_flip($data[0]);

        foreach (array_slice($data, 1) as $ligne)
        {
            if (empty($ligne[$indexColonnes['etudid']])) break;
            $this->traiterEtudiant($ligne, $indexColonnes, $numeroSemestre, $anneePromotion);
        }
    }

    private function traiterEtudiant($ligne, $indexColonnes, $numeroSemestre, $anneePromotion)
    {
        $db = db_connect();
        
        $idEtudiant = $ligne[$indexColonnes['etudid']];
        $nomEtudiant = $ligne[$indexColonnes['Nom']];
        $prenomEtudiant = $ligne[$indexColonnes['Prénom']];
        $parcoursEtudes = $ligne[$indexColonnes['Cursus']];
        $nbAbsencesInjust = $ligne[$indexColonnes['Abs']] - $ligne[$indexColonnes['Just.']];

        // Insertion étudiant
        $db->query('INSERT INTO "Etudiant" ("idEtudiant", "nomEtudiant", "prenomEtudiant", "parcoursEtudes", "anneePromotion")
                    VALUES (?, ?, ?, ?, ?) ON CONFLICT ("idEtudiant") DO NOTHING',
                    [$idEtudiant, $nomEtudiant, $prenomEtudiant, $parcoursEtudes, $anneePromotion]);

        // Insertion semestre
        $db->query('INSERT INTO "Semestre" ("idEtudiant", "numeroSemestre", "nbAbsencesInjust") 
                    VALUES (?, ?, ?) ON CONFLICT ("idEtudiant", "numeroSemestre") DO UPDATE SET "nbAbsencesInjust" = ?',
                    [$idEtudiant, $numeroSemestre, $nbAbsencesInjust, $nbAbsencesInjust]);

        // Ressources par semestre
        $ressourcesVoulues = [
            1 => ['BINR106', 'BINR107', 'BINR110'],
            2 => ['BINR207', 'BINR208', 'BINR209', 'BINR212'],
            3 => ['BINR308', 'BINR309', 'BINR312'],
            4 => ['BINR404', 'BINR412', 'BINR405'],
            5 => ['BINR511', 'BINR512']
        ];

        foreach ($indexColonnes as $nomColonne => $j)
        {
            if (empty($ligne[$j]) || $ligne[$j] === '~') continue;

            // Compétences
            if (preg_match('/^BIN\d+$/', $nomColonne))
            {
                $db->query('INSERT INTO "Competence" ("idEtudiant", "numeroSemestre", "codeCompetence", "moyenneCompetence")
                            VALUES (?, ?, ?, ?) ON CONFLICT("idEtudiant", "numeroSemestre", "codeCompetence") DO UPDATE SET "moyenneCompetence" = ?',
                            [$idEtudiant, $numeroSemestre, $nomColonne, $ligne[$j], $ligne[$j]]);
            }

            // Ressources
            if (isset($ressourcesVoulues[$numeroSemestre]) && in_array($nomColonne, $ressourcesVoulues[$numeroSemestre]))
            {
                $db->query('INSERT INTO "Ressource" ("idEtudiant", "numeroSemestre", "codeRessource", "moyenneRessource")
                            VALUES (?, ?, ?, ?) ON CONFLICT("idEtudiant", "numeroSemestre", "codeRessource") DO UPDATE SET "moyenneRessource" = ?',
                            [$idEtudiant, $numeroSemestre, $nomColonne, $ligne[$j], $ligne[$j]]);
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
                            COALESCE(SUM(CASE WHEN "numeroSemestre" IN (5) THEN "nbAbsencesInjust" END), 0) as but3
                            FROM "Semestre" WHERE "idEtudiant" = ?', [$idEtudiant])->getRow();

        return $this->response->setJSON([
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
                                COALESCE(MAX(CASE WHEN "numeroSemestre" IN (5) THEN "apprentissage" END), \'\') as but3
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

    private function sauvegarderAvisCommentaire($type, $idEtudiant, $data)
    {
        $db = db_connect();
        
        if ($type === 'avis') {
            $db->query('INSERT INTO "Avis" ("idEtudiant", "typePoursuite", "typeAvis") 
                        VALUES (?, ?, ?) ON CONFLICT ("idEtudiant", "typePoursuite") DO UPDATE SET "typeAvis" = ?',
                        [$idEtudiant, $data['typePoursuite'], $data['typeAvis'], $data['typeAvis']]);
        } else {
            $db->query('INSERT INTO "Avis" ("idEtudiant", "typePoursuite", "typeAvis", "commentaire") 
                        VALUES (?, ?, ?, ?) ON CONFLICT ("idEtudiant", "typePoursuite") DO UPDATE SET "commentaire" = ?',
                        [$idEtudiant, 'commentaire_general', 'sans_avis', $data['commentaire'], $data['commentaire']]);
        }
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
        
        try {
            $this->sauvegarderAvisCommentaire('avis', $json['idEtudiant'], $json);
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
        
        try {
            $this->sauvegarderAvisCommentaire('commentaire', $json['idEtudiant'], $json);
            return $this->response->setJSON(['success' => true, 'message' => 'Commentaire sauvegardé']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
        }
    }

    public function avisParEtudiant($idEtudiant)
    {
        $db = db_connect();
        $resultat = $db->query('SELECT "typePoursuite", "typeAvis", "commentaire" FROM "Avis" WHERE "idEtudiant" = ?', [$idEtudiant])->getResultArray();
        
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
        
        $totalAvis = $db->query('SELECT COUNT(DISTINCT a."idEtudiant") as "total"
                                FROM "Avis" a INNER JOIN "Etudiant" e ON a."idEtudiant" = e."idEtudiant"
                                WHERE e."anneePromotion" = ? AND a."typePoursuite" IN (?, ?)',
                                [$anneePromotion, 'ecole_ingenieur', 'master'])->getRow();
        
        $stats = [
            'ecole_ingenieur' => ['tres_favorable' => 0, 'favorable' => 0, 'assez_favorable' => 0, 'sans_avis' => 0, 'reserve' => 0],
            'master' => ['tres_favorable' => 0, 'favorable' => 0, 'assez_favorable' => 0, 'sans_avis' => 0, 'reserve' => 0],
            'totalAvisPromotion' => intval($totalAvis->total ?? 0)
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
        
        if (!isset($json['idEtudiant'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID étudiant manquant']);
        }
        
        $db = db_connect();
        
        try {
            if (isset($json['mobilite_etranger'])) {
                $db->query('UPDATE "Etudiant" SET "mobiliteEtranger" = ? WHERE "idEtudiant" = ?',
                          [$json['mobilite_etranger'], $json['idEtudiant']]);
            }
            
            if (isset($json['apprentissage_but3'])) {
                $db->query('UPDATE "Semestre" SET "apprentissage" = ? WHERE "idEtudiant" = ? AND "numeroSemestre" = 5',
                          [$json['apprentissage_but3'], $json['idEtudiant']]);
            }
            
            return $this->response->setJSON(['success' => true, 'message' => 'Données mises à jour']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
        }
    }
}