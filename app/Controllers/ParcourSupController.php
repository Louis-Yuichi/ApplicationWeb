<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ParcourSupController extends BaseController
{
    private $noteCalculator;
    private $importExportService;
    
    public function __construct()
    {
        $this->noteCalculator = new \App\Services\NoteCalculatorService();
        $this->importExportService = new \App\Services\ImportExportService();
    }

    public function menu()
    {
        $data = [
            'success' => session()->getFlashdata('success'),
            'error' => session()->getFlashdata('error')
        ];
        
        $this->view('parcoursup/parcoursup.html.twig', $data);
    }

    public function filtres()
    {
        $filtreModel = new \App\Models\FiltreModel();
        $filtres = $filtreModel->findAll();
        
        // RÉCUPÉRER TOUTES LES COLONNES DISPONIBLES pour les filtres
        $candidatModel = new \App\Models\CandidatModel();
        $etablissementModel = new \App\Models\EtablissementModel();
        $etudierDansModel = new \App\Models\EtudierDansModel();
        
        // Construire la liste complète des colonnes avec préfixes pour éviter les conflits
        $colonnesDisponibles = [];
        
        // Colonnes du candidat (sans préfixe car c'est la table principale)
        foreach ($candidatModel->allowedFields as $field) {
            $colonnesDisponibles[$field] = "Candidat - " . ucfirst(str_replace('_', ' ', $field));
        }
        
        // Colonnes de l'établissement (avec préfixe)
        foreach ($etablissementModel->allowedFields as $field) {
            $colonnesDisponibles[$field] = "Établissement - " . ucfirst(str_replace('_', ' ', $field));
        }
        
        // Colonnes d'EtudierDans (avec préfixe, en excluant les clés)
        $etudierDansFields = array_diff($etudierDansModel->allowedFields, ['numCandidat', 'idEtablissement', 'anneeUniversitaire']);
        foreach ($etudierDansFields as $field) {
            $colonnesDisponibles[$field] = "Notes - " . ucfirst(str_replace('_', ' ', $field));
        }
        
        $data = [
            'filtres' => $filtres,
            'colonnesDisponibles' => $colonnesDisponibles,
            'success' => session()->getFlashdata('success'),
            'error' => session()->getFlashdata('error')
        ];
        
        return $this->view('parcoursup/filtrer/filtres.html.twig', $data);
    }

    public function creerFiltre()
    {
        $filtreModel = new \App\Models\FiltreModel();
        
        $data = [
            'nomFiltre' => $this->request->getPost('nomFiltre'),
            'typeAction' => $this->request->getPost('typeAction'),
            'valeurAction' => $this->request->getPost('valeurAction'),
            'colonneSource' => $this->request->getPost('colonneSource'),
            'conditionType' => $this->request->getPost('conditionType'),
            'valeurCondition' => $this->request->getPost('valeurCondition'),
            'actif' => $this->request->getPost('actif') ? 1 : 0
        ];
        
        if ($filtreModel->insert($data)) {
            return redirect()->to('/filtres');
        } else {
            return redirect()->to('/filtres')->with('error', 'Erreur lors de la création du filtre');
        }
    }

    public function toggleFiltre($idFiltre)
    {
        $filtreModel = new \App\Models\FiltreModel();
        $filtre = $filtreModel->find($idFiltre);
        
        if ($filtre) {
            $nouveauStatut = $filtre['actif'] ? 0 : 1;
            $filtreModel->update($idFiltre, ['actif' => $nouveauStatut]);
            return redirect()->to('/filtres');
        }
        
        return redirect()->to('/filtres')->with('error', 'Filtre non trouvé');
    }

    public function supprimerFiltre($idFiltre)
    {
        $filtreModel = new \App\Models\FiltreModel();
        
        if ($filtreModel->delete($idFiltre)) {
            return redirect()->to('/filtres');
        } else {
            return redirect()->to('/filtres')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function gestion()
    {
        $candidatModel = new \App\Models\CandidatModel();
        $etablissementModel = new \App\Models\EtablissementModel();
        $etudierDansModel = new \App\Models\EtudierDansModel();

        $anneeSelectionnee = $this->request->getGet('annee');
        
        $columns = [
            'Candidat' => array_diff($candidatModel->allowedFields, ['noteGlobale', 'noteDossier', 'commentaire']),
            'Etablissement' => $etablissementModel->allowedFields,
            'Candidat_noteGlobale' => ['noteGlobale'],
            'EtudierDans' => array_values(array_diff($etudierDansModel->allowedFields, ['numCandidat', 'idEtablissement', 'anneeUniversitaire'])),
            'Candidat_fin' => ['noteDossier', 'commentaire']
        ];

        $candidats = [];
        if ($anneeSelectionnee) {
            try {
                $candidats = $candidatModel->select('
                    Candidat.*,
                    Etablissement.*,
                    EtudierDans.noteLycee,
                    EtudierDans.noteFicheAvenir
                ')
                ->join('EtudierDans', 'EtudierDans.numCandidat = Candidat.numCandidat AND EtudierDans.anneeUniversitaire = Candidat.anneeUniversitaire')
                ->join('Etablissement', 'Etablissement.idEtablissement = EtudierDans.idEtablissement')
                ->where('Candidat.anneeUniversitaire', $anneeSelectionnee)
                ->findAll();
            } catch (\Exception $e) {
                // Silence
            }
        }

        return $this->view('parcoursup/gestion.html.twig', [
            'candidats' => $candidats,
            'columns' => $columns,
            'anneeSelectionnee' => $anneeSelectionnee
        ]);
    }

    public function importer()
    {
        if (!$this->request->getFile('fichier')) {
            return redirect()->back()->with('error', 'Aucun fichier sélectionné');
        }

        $file = $this->request->getFile('fichier');
        $annee = $this->request->getPost('annee');

        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($file);
            $sheet = $spreadsheet->getActiveSheet();

            $header = [];
            foreach ($sheet->getRowIterator(1, 1) as $row) {
                $header = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0];
            }

            // Utiliser le service
            $mapping = $this->importExportService->mapColumns($header);

            $requiredFields = ['numCandidat', 'nom', 'prenom'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($mapping[$field])) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                throw new \Exception('Champs obligatoires manquants : ' . implode(', ', $missingFields));
            }

            $etablissementModel = new \App\Models\EtablissementModel();
            $candidatModel = new \App\Models\CandidatModel();

            $db = \Config\Database::connect();
            $db->transStart();
            
            $nbInserted = 0;

            foreach ($sheet->getRowIterator(2) as $row) {
                $rowData = $sheet->rangeToArray('A' . $row->getRowIndex() . ':' . $sheet->getHighestColumn() . $row->getRowIndex())[0];
                if (empty(array_filter($rowData))) continue;

                try {
                    $numCandidat = strval($rowData[$mapping['numCandidat']] ?? '');
                    if (empty($numCandidat)) continue;
                    
                    $numCandidat = trim($numCandidat);

                    // Données candidat
                    $candidatData = [
                        'numCandidat' => strval($numCandidat),
                        'anneeUniversitaire' => strval($annee)
                    ];

                    if (isset($mapping['nom'])) {
                        $candidatData['nom'] = strval($rowData[$mapping['nom']] ?? '') ?: '-';
                    }
                    if (isset($mapping['prenom'])) {
                        $candidatData['prenom'] = strval($rowData[$mapping['prenom']] ?? '') ?: '-';
                    }

                    $textFields = [
                        'civilite', 'profil', 'scolarite', 'diplome', 
                        'typeDiplomeCode', 'preparation_obtenu', 'serie', 'serieCode', 
                        'specialitesTerminale', 'specialiteAbandonne', 'specialiteMention', 'commentaire'
                    ];

                    foreach ($textFields as $field) {
                        if (isset($mapping[$field])) {
                            $value = strval($rowData[$mapping[$field]] ?? '');
                            $candidatData[$field] = empty($value) ? '-' : $value;
                        } else {
                            $candidatData[$field] = '-';
                        }
                    }

                    // TRAITEMENT SPÉCIAL POUR FORMATION - choisir la colonne avec données
                    $formationColumnIndex = $this->importExportService->chooseFormationColumn($header, $mapping, $rowData);
                    if ($formationColumnIndex !== null) {
                        $formationValue = strval($rowData[$formationColumnIndex] ?? '');
                        $candidatData['formation'] = empty($formationValue) ? '-' : $formationValue;
                    } else {
                        $candidatData['formation'] = '-';
                    }

                    // TRAITEMENT SPÉCIAL POUR MARQUEURS DOSSIER
                    if (isset($mapping['marqueurDossier'])) {
                        $value = strval($rowData[$mapping['marqueurDossier']] ?? '');
                        $candidatData['marqueurDossier'] = empty($value) ? '-' : $value;
                    } else {
                        $candidatData['marqueurDossier'] = '-';
                    }

                    // Traiter le champ boursier séparément
                    $candidatData['boursier'] = isset($mapping['boursier']) ? 
                        $this->importExportService->formatBoursier($rowData[$mapping['boursier']] ?? '') : '0';

                    $numericFields = ['noteDossier', 'noteGlobale'];
                    foreach ($numericFields as $field) {
                        $candidatData[$field] = 0;
                        if (isset($mapping[$field]) && 
                            isset($rowData[$mapping[$field]]) && 
                            !empty($rowData[$mapping[$field]]) && 
                            is_numeric($rowData[$mapping[$field]])) {
                            $candidatData[$field] = floatval($rowData[$mapping[$field]]);
                        }
                    }

                    // Insertion/update candidat
                    $existingCandidat = $db->table('Candidat')
                        ->where('numCandidat', $numCandidat)
                        ->where('anneeUniversitaire', $annee)
                        ->get()
                        ->getRow();

                    if ($existingCandidat) {
                        $db->table('Candidat')
                            ->where('numCandidat', $numCandidat)
                            ->where('anneeUniversitaire', $annee)
                            ->update($candidatData);
                    } else {
                        $db->table('Candidat')->insert($candidatData);
                    }
                    
                    // Gestion établissement avec le service
                    $etablissementData = [
                        'nomEtablissement' => isset($mapping['nomEtablissement']) ? strval($rowData[$mapping['nomEtablissement']] ?? '') : '',
                        'villeEtablissement' => isset($mapping['villeEtablissement']) ? strval($rowData[$mapping['villeEtablissement']] ?? '') : '',
                        'codePostalEtablissement' => isset($mapping['codePostalEtablissement']) ? strval($rowData[$mapping['codePostalEtablissement']] ?? '') : '',
                        'departementEtablissement' => isset($mapping['departementEtablissement']) ? strval($rowData[$mapping['departementEtablissement']] ?? '') : '',
                        'paysEtablissement' => isset($mapping['paysEtablissement']) ? strval($rowData[$mapping['paysEtablissement']] ?? '') : ''
                    ];

                    if (!empty($etablissementData['nomEtablissement']) || !empty($etablissementData['villeEtablissement'])) {
                        $etablissement = $this->importExportService->getOrCreateEtablissement($etablissementModel, $etablissementData);
                    } else {
                        $etablissementData = [
                            'nomEtablissement' => 'Non renseigné',
                            'villeEtablissement' => 'Non renseigné',
                            'codePostalEtablissement' => '',
                            'departementEtablissement' => '',
                            'paysEtablissement' => ''
                        ];
                        $etablissement = $this->importExportService->getOrCreateEtablissement($etablissementModel, $etablissementData);
                    }

                    // Relation EtudierDans
                    if ($etablissement && isset($etablissement['idEtablissement'])) {
                        $noteLycee = null;
                        if (isset($mapping['noteLycee']) && 
                            isset($rowData[$mapping['noteLycee']]) && 
                            is_numeric($rowData[$mapping['noteLycee']])) {
                            $noteLycee = number_format(floatval($rowData[$mapping['noteLycee']]), 2, '.', '');
                        }
                        
                        $noteFicheAvenir = null;
                        if (isset($mapping['noteFicheAvenir']) && 
                            isset($rowData[$mapping['noteFicheAvenir']]) && 
                            is_numeric($rowData[$mapping['noteFicheAvenir']])) {
                            $noteFicheAvenir = number_format(floatval($rowData[$mapping['noteFicheAvenir']]), 2, '.', '');
                        }

                        $etudierDansData = [
                            'numCandidat' => strval($numCandidat),
                            'anneeUniversitaire' => strval($annee),
                            'idEtablissement' => intval($etablissement['idEtablissement']),
                            'noteLycee' => $noteLycee,
                            'noteFicheAvenir' => $noteFicheAvenir
                        ];

                        $db->table('EtudierDans')
                            ->where('numCandidat', $numCandidat)
                            ->where('anneeUniversitaire', $annee)
                            ->where('idEtablissement', $etablissement['idEtablissement'])
                            ->delete();

                        $db->table('EtudierDans')->insert($etudierDansData);
                    }

                    $nbInserted++;

                } catch (\Exception $e) {
                    continue;
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return redirect()->back()->with('error', 'Erreur lors de la transaction');
            }

            if ($nbInserted === 0) {
                return redirect()->back()->with('error', 'Aucun candidat n\'a été importé');
            } else {
                return redirect()->to('/parcoursup')->with('success', "$nbInserted candidat(s) importé(s) avec succès");
            }

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Une erreur est survenue pendant l\'import');
        }
    }

    public function modifierCandidat()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Requête non autorisée']);
        }
        
        $json = $this->request->getJSON(true);
        
        if (!isset($json['numCandidat'], $json['anneeUniversitaire'], $json['field'], $json['value'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Données manquantes']);
        }
        
        $numCandidat = $json['numCandidat'];
        $anneeUniversitaire = $json['anneeUniversitaire'];
        $field = $json['field'];
        $value = $json['value'];
        
        $candidatModel = new \App\Models\CandidatModel();
        $allowedFields = array_diff($candidatModel->allowedFields, ['numCandidat', 'anneeUniversitaire']);
        
        if (!in_array($field, $allowedFields)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Champ non modifiable']);
        }
        
        try {
            $db = \Config\Database::connect();
            
            $candidat = $db->table('Candidat')
                ->where('numCandidat', $numCandidat)
                ->where('anneeUniversitaire', $anneeUniversitaire)
                ->get()
                ->getRow();
                
            if (!$candidat) {
                return $this->response->setJSON(['success' => false, 'message' => 'Candidat non trouvé']);
            }
            
            // Utiliser le service
            $processedValue = $this->importExportService->processFieldValue($field, $value);
            
            $result = $db->table('Candidat')
                ->where('numCandidat', $numCandidat)
                ->where('anneeUniversitaire', $anneeUniversitaire)
                ->update([$field => $processedValue]);
                
            if ($result) {
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => 'Modification sauvegardée',
                    'newValue' => $processedValue
                ]);
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
            }
            
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Erreur serveur']);
        }
    }

    public function calculerNotesAjax()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Requête non autorisée']);
        }
        
        try {
            $json = $this->request->getJSON(true);
            $annee = $json['annee'] ?? '';
            
            if (empty($annee)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Année non spécifiée']);
            }
            
            $filtreModel = new \App\Models\FiltreModel();
            $candidatModel = new \App\Models\CandidatModel();
            
            $filtres = $filtreModel->where('actif', 1)->findAll();
            
            if (empty($filtres)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Aucun filtre actif trouvé']);
            }
            
            $candidats = $candidatModel->select('
                    Candidat.*,
                    Etablissement.*,
                    EtudierDans.noteLycee,
                    EtudierDans.noteFicheAvenir
                ')
                ->join('EtudierDans', 'EtudierDans.numCandidat = Candidat.numCandidat AND EtudierDans.anneeUniversitaire = Candidat.anneeUniversitaire')
                ->join('Etablissement', 'Etablissement.idEtablissement = EtudierDans.idEtablissement')
                ->where('Candidat.anneeUniversitaire', $annee)
                ->findAll();
            
            if (empty($candidats)) {
                return $this->response->setJSON(['success' => false, 'message' => "Aucun candidat trouvé pour l'année {$annee}"]);
            }
            
            $nbMisAJour = 0;
            $notesCalculees = [];
            $notesGlobales = [];
            $db = \Config\Database::connect();
            
            foreach ($candidats as $candidat) {
                // Utiliser les services
                $noteDossierCalculee = $this->noteCalculator->appliquerFiltres($candidat, $filtres);
                $noteGlobaleCalculee = $this->noteCalculator->calculerNoteGlobale(
                    $candidat['noteLycee'],
                    $candidat['noteFicheAvenir'], 
                    $noteDossierCalculee
                );
                
                $result = $db->table('Candidat')
                    ->where('numCandidat', $candidat['numCandidat'])
                    ->where('anneeUniversitaire', $annee)
                    ->update([
                        'noteDossier' => $noteDossierCalculee,
                        'noteGlobale' => $noteGlobaleCalculee
                    ]);
                
                if ($result) {
                    $nbMisAJour++;
                    $notesCalculees[$candidat['numCandidat']] = number_format($noteDossierCalculee, 2);
                    $notesGlobales[$candidat['numCandidat']] = number_format($noteGlobaleCalculee, 2);
                }
            }
            
            return $this->response->setJSON([
                'success' => true,
                'message' => "Notes calculées avec succès pour l'année {$annee}",
                'nbMisAJour' => $nbMisAJour,
                'notes' => $notesCalculees,
                'notesGlobales' => $notesGlobales,
                'annee' => $annee
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Erreur serveur lors du calcul']);
        }
    }

    public function evaluation()
    {
        $codeExaminateur = $this->request->getGet('codeExaminateur');
        $anneeSelectionnee = $this->request->getGet('annee');
        
        if (empty($codeExaminateur)) {
            return redirect()->to('/gestionParcourSup')->with('error', 'Code examinateur manquant');
        }
        
        if (empty($anneeSelectionnee)) {
            return redirect()->to('/gestionParcourSup')->with('error', 'Année non sélectionnée');
        }
        
        $utilisateur = $this->getCurrentUser();
        $nomExaminateur = $utilisateur['nom'] ?? 'Nom';
        $prenomExaminateur = $utilisateur['prenom'] ?? 'Prénom';
        
        $candidatModel = new \App\Models\CandidatModel();
        
        try {
            $candidats = $candidatModel->select('
                    Candidat.numCandidat,
                    Candidat.nom,
                    Candidat.prenom,
                    Candidat.noteDossier,
                    Candidat.commentaire,
                    Candidat.profil as groupe
                ')
                ->join('EtudierDans', 'EtudierDans.numCandidat = Candidat.numCandidat AND EtudierDans.anneeUniversitaire = Candidat.anneeUniversitaire')
                ->join('Etablissement', 'Etablissement.idEtablissement = EtudierDans.idEtablissement')
                ->where('Candidat.anneeUniversitaire', $anneeSelectionnee)
                ->orderBy('Candidat.nom', 'ASC')
                ->orderBy('Candidat.prenom', 'ASC')
                ->findAll();
        } catch (\Exception $e) {
            return redirect()->to('/gestionParcourSup')->with('error', 'Erreur lors de la récupération des données');
        }
        
        $columns = [
            'numCandidat' => 'Code Candidat',
            'nom' => 'Nom candidat', 
            'prenom' => 'Prénom candidat',
            'groupe' => 'Groupe',
            'codeExaminateur' => 'Code examinateur',
            'nomExaminateur' => 'Nom examinateur',
            'prenomExaminateur' => 'Prénom examinateur',
            'noteDossier' => 'Note de Dossier',
            'commentaire' => 'Commentaire'
        ];
        
        foreach ($candidats as &$candidat) {
            $candidat['codeExaminateur'] = $codeExaminateur;
            $candidat['nomExaminateur'] = $nomExaminateur;
            $candidat['prenomExaminateur'] = $prenomExaminateur;
            
            $candidat['noteDossier'] = number_format($candidat['noteDossier'], 2);
            $candidat['commentaire'] = $candidat['commentaire'] ?: '-';
            $candidat['groupe'] = $candidat['groupe'] ?: '-';
        }
        
        return $this->view('parcoursup/evaluation.html.twig', [
            'candidats' => $candidats,
            'columns' => $columns,
            'anneeSelectionnee' => $anneeSelectionnee,
            'examinateur' => [
                'code' => $codeExaminateur,
                'nom' => $nomExaminateur,
                'prenom' => $prenomExaminateur
            ],
            'nbCandidats' => count($candidats)
        ]);
    }

    public function exporterEvaluationAvecModifications()
    {
        $exportDataJson = $this->request->getPost('exportData');
        $codeExaminateur = $this->request->getPost('codeExaminateur');
        $anneeSelectionnee = $this->request->getPost('annee');
        
        if (empty($exportDataJson) || empty($codeExaminateur) || empty($anneeSelectionnee)) {
            return redirect()->back()->with('error', 'Données d\'export manquantes');
        }
        
        $exportData = json_decode($exportDataJson, true);
        
        if (!$exportData) {
            return redirect()->back()->with('error', 'Erreur lors du décodage des données');
        }
        
        $utilisateur = $this->getCurrentUser();
        $nomExaminateur = $utilisateur['nom'] ?? 'Nom Utilisateur';
        $prenomExaminateur = $utilisateur['prenom'] ?? 'Prénom Utilisateur';
        
        try {
            $csvData = [];
            foreach ($exportData as $candidat) {
                $csvData[] = [
                    $candidat['numCandidat'] ?? '',
                    $candidat['nom'] ?? '',
                    $candidat['prenom'] ?? '',
                    $candidat['groupe'] ?? '-',
                    $codeExaminateur,
                    $nomExaminateur,
                    $prenomExaminateur,
                    $candidat['noteDossier'] ?? '0.00',
                    $candidat['commentaire'] ?? '-'
                ];
            }
            
            $headers = [
                'Code Candidat', 'Nom candidat', 'Prénom candidat', 
                'Groupe', 'Code examinateur', 'Nom examinateur', 
                'Prénom examinateur', 'Note de Dossier', 'Commentaire'
            ];
            
            $filename = "evaluation_modifiee_" . str_replace('/', '-', $anneeSelectionnee) . "_" . date('Y-m-d_H-i-s') . ".csv";
            
            // Utiliser le service
            $this->importExportService->generateCSV($csvData, $headers, $filename);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'export');
        }
    }
}
