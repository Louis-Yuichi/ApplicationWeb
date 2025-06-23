<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ParcourSupController extends BaseController
{
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
        
        // Récupérer les colonnes disponibles pour les filtres
        $candidatModel = new \App\Models\CandidatModel();
        $colonnesDisponibles = $candidatModel->allowedFields;
        
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

    public function calculerNotes()
    {
        $filtreModel = new \App\Models\FiltreModel();
        $candidatModel = new \App\Models\CandidatModel();
        
        $filtres = $filtreModel->where('actif', 1)->findAll();
        
        if (empty($filtres)) {
            return redirect()->to('/filtres')->with('error', 'Aucun filtre actif trouvé');
        }
        
        $candidats = $candidatModel->select('
                Candidat.*,
                Etablissement.*,
                EtudierDans.noteLycee,
                EtudierDans.noteFicheAvenir
            ')
            ->join('EtudierDans', 'EtudierDans.numCandidat = Candidat.numCandidat AND EtudierDans.anneeUniversitaire = Candidat.anneeUniversitaire')
            ->join('Etablissement', 'Etablissement.idEtablissement = EtudierDans.idEtablissement')
            ->findAll();
        
        foreach ($candidats as $candidat) {
            $noteCalculee = $this->appliquerFiltres($candidat, $filtres);
            
            $candidatModel->update([
                'numCandidat' => $candidat['numCandidat'],
                'anneeUniversitaire' => $candidat['anneeUniversitaire']
            ], [
                'noteDossier' => $noteCalculee
            ]);
        }
        
        return redirect()->to('/filtres');
    }

    private function appliquerFiltres($candidat, $filtres)
    {
        // Utiliser noteLycee comme note de base, sinon 10
        $noteBase = 10;
        
        if (isset($candidat['noteLycee']) && !empty($candidat['noteLycee']) && is_numeric($candidat['noteLycee'])) {
            $noteBase = floatval($candidat['noteLycee']);
        }
        
        $noteFinale = $noteBase;
        
        foreach ($filtres as $filtre) {
            if ($this->evaluerCondition($candidat, $filtre)) {
                switch ($filtre['typeAction']) {
                    case 'bonus':
                        $noteFinale += $filtre['valeurAction'];
                        break;
                    case 'malus':
                        $noteFinale -= abs($filtre['valeurAction']);
                        break;
                    case 'coefficient':
                        $noteFinale *= $filtre['valeurAction'];
                        break;
                    case 'note_directe':
                        $noteFinale = $filtre['valeurAction'];
                        break;
                }
            }
        }
        
        // S'assurer que la note reste dans les limites (0-20)
        $noteFinale = max(0, min(20, $noteFinale));
        
        return $noteFinale;
    }

    private function evaluerCondition($candidat, $filtre)
    {
        $valeurCandidat = $candidat[$filtre['colonneSource']] ?? '';
        $valeurCondition = $filtre['valeurCondition'];
        
        switch ($filtre['conditionType']) {
            case 'contient':
                return stripos($valeurCandidat, $valeurCondition) !== false;
            case 'egal':
                return strcasecmp($valeurCandidat, $valeurCondition) === 0;
            case 'different':
                return strcasecmp($valeurCandidat, $valeurCondition) !== 0;
            case 'commence_par':
                return stripos($valeurCandidat, $valeurCondition) === 0;
            case 'finit_par':
                return strlen($valeurCondition) <= strlen($valeurCandidat) && 
                       strcasecmp(substr($valeurCandidat, -strlen($valeurCondition)), $valeurCondition) === 0;
            default:
                return false;
        }
    }

    private $columnMappings = [
        'candidat' => [
            'code' => 'numCandidat',
            'nom' => 'nom',
            'prénom' => 'prenom',
            'civilité' => 'civilite',
            'profil' => 'profil',
            'boursier' => 'boursier',
            'marqueur_dossier' => 'marqueurDossier',
            'marqueurs dossier' => 'marqueurDossier'
        ],
        'filiere' => [
            'libellé' => 'scolarite'
        ],
        'formation' => [
            'libellé' => 'formation'
        ],
        'diplome' => [
            'libellé' => 'diplome'
        ],
        'type diplôme' => [
            'code' => 'typeDiplomeCode',
            'libellé' => 'typeDiplome'
        ],
        'preparation' => [
            'obtenu' => 'preparation_obtenu'
        ],
        'série diplôme' => [
            'libellé' => 'serie',
            'code' => 'serieCode'
        ],
        'série' => [
            '' => 'serie'
        ],
        'scolarité' => [
            '2022/2023' => 'scolarite'
        ],
        'en préparation' => [
            'obtenu' => 'preparation_obtenu'
        ],
        'combinaison' => [
            'enseignements' => 'specialitesTerminale',
            'spécialité' => 'specialitesTerminale'
        ],
        'enseignement' => [
            'spécialité abandonné' => 'specialiteAbandonne'
        ],
        'spécialité' => [
            'terminale' => 'specialitesTerminale',
            'abandonné' => 'specialiteAbandonne',
            'mention' => 'specialiteMention',
            'bac pro' => 'specialiteMention'
        ],
        'commentaire' => [
            '' => 'commentaire'
        ],
        'commentaires' => [
            '' => 'commentaire'
        ],
        'établissement' => [
            'nom établissement origine' => 'nomEtablissement',
            'commune etablissement origine - libellé' => 'villeEtablissement',
            'commune etablissement origine - codepostal' => 'codePostalEtablissement',
            'département etablissement origine' => 'departementEtablissement',
            'pays etablissement origine' => 'paysEtablissement'
        ],
        'note' => [
            'globale' => 'noteGlobale',
            'fiche avenir' => 'noteFicheAvenir',
            'lycée' => 'noteLycee',
            'dossier' => 'noteDossier',
            'globale calculée' => 'noteGlobale'
        ]
    ];

    public function gestion()
    {
        // Récupération des modèles
        $candidatModel = new \App\Models\CandidatModel();
        $etablissementModel = new \App\Models\EtablissementModel();
        $etudierDansModel = new \App\Models\EtudierDansModel();

        // Récupération de l'année sélectionnée
        $anneeSelectionnee = $this->request->getGet('annee');
        
        // Récupération des champs dans l'ordre souhaité
        $columns = [
            'Candidat' => array_diff($candidatModel->allowedFields, ['noteGlobale', 'noteDossier', 'commentaire']),
            'Etablissement' => $etablissementModel->allowedFields,
            'Candidat_noteGlobale' => ['noteGlobale'],
            'EtudierDans' => array_values(array_diff($etudierDansModel->allowedFields, ['numCandidat', 'idEtablissement', 'anneeUniversitaire'])),
            'Candidat_fin' => ['noteDossier', 'commentaire']
        ];

        // Récupération des données uniquement si une année est sélectionnée
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
                // Silence - pas de log
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
        if (!$this->request->getFile('fichier'))
        {
            return redirect()->back()->with('error', 'Aucun fichier sélectionné');
        }

        $file = $this->request->getFile('fichier');
        $annee = $this->request->getPost('annee');

        try
        {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($file);
            $sheet = $spreadsheet->getActiveSheet();

            $header = [];
            foreach ($sheet->getRowIterator(1, 1) as $row)
            {
                $header = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0];
            }

            // Création du mapping dynamique
            $mapping = $this->mapColumns($header);

            // Vérification du mapping après la boucle
            $requiredFields = ['numCandidat', 'nom', 'prenom'];
            $missingFields = [];
            foreach ($requiredFields as $field)
            {
                if (!isset($mapping[$field]))
                {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields))
            {
                throw new \Exception('Champs obligatoires manquants : ' . implode(', ', $missingFields));
            }

            // Initialisation des modèles
            $etablissementModel = new \App\Models\EtablissementModel();
            $candidatModel = new \App\Models\CandidatModel();
            $etudierDansModel = new \App\Models\EtudierDansModel();

            $db = \Config\Database::connect();
            $db->transStart();
            
            $nbInserted = 0;

            foreach ($sheet->getRowIterator(2) as $row)
            {
                $rowData = $sheet->rangeToArray('A' . $row->getRowIndex() . ':' . $sheet->getHighestColumn() . $row->getRowIndex())[0];
                if (empty(array_filter($rowData))) continue;

                try {
                    // 1. Vérification et préparation du numCandidat
                    $numCandidat = strval($rowData[$mapping['numCandidat']] ?? '');
                    if (empty($numCandidat))
                    {
                        continue;
                    }
                    
                    $numCandidat = trim($numCandidat);

                    // 2. Préparation des données du candidat de base
                    $candidatData = [
                        'numCandidat' => strval($numCandidat),
                        'anneeUniversitaire' => strval($annee)
                    ];

                    // Ajout des champs obligatoires
                    if (isset($mapping['nom']))
                    {
                        $candidatData['nom'] = strval($rowData[$mapping['nom']] ?? '') ?: '-';
                    }
                    if (isset($mapping['prenom']))
                    {
                        $candidatData['prenom'] = strval($rowData[$mapping['prenom']] ?? '') ?: '-';
                    }

                    // Traiter les champs textuels simples
                    $textFields = [
                        'civilite', 'profil', 'formation', 'scolarite', 'diplome', 
                        'typeDiplomeCode', 'preparation_obtenu', 'serie', 'serieCode', 
                        'specialitesTerminale', 'specialiteAbandonne', 'specialiteMention', 'commentaire'
                    ];

                    foreach ($textFields as $field)
                    {
                        if (isset($mapping[$field]))
                        {
                            $value = strval($rowData[$mapping[$field]] ?? '');
                            $candidatData[$field] = empty($value) ? '-' : $value;
                        } else {
                            $candidatData[$field] = '-';
                        }
                    }

                    // Traiter le champ boursier séparément
                    $candidatData['boursier'] = isset($mapping['boursier']) ? 
                        $this->formatBoursier($rowData[$mapping['boursier']] ?? '') : '0';

                    // Traiter les champs numériques de Candidat uniquement
                    $numericFields = ['noteDossier', 'noteGlobale'];
                    foreach ($numericFields as $field)
                    {
                        $candidatData[$field] = 0;
                        if (isset($mapping[$field]) && 
                            isset($rowData[$mapping[$field]]) && 
                            !empty($rowData[$mapping[$field]]) && 
                            is_numeric($rowData[$mapping[$field]]))
                            {
                            $candidatData[$field] = floatval($rowData[$mapping[$field]]);
                        }
                    }

                    // Insertion ou mise à jour simplifiée
                    try {
                        $db = \Config\Database::connect();
                        
                        // Vérifier si le candidat existe pour cette année spécifique
                        $existingCandidat = $db->table('Candidat')
                            ->where('numCandidat', $numCandidat)
                            ->where('anneeUniversitaire', $annee)
                            ->get()
                            ->getRow();

                        if ($existingCandidat)
                        {
                            $db->table('Candidat')
                                ->where('numCandidat', $numCandidat)
                                ->where('anneeUniversitaire', $annee)
                                ->update($candidatData);
                        } else {
                            $db->table('Candidat')->insert($candidatData);
                        }
                        
                        $nbInserted++;

                    } catch (\Exception $e)
                    {
                        continue;
                    }

                    // 4. Gestion de l'établissement
                    $etablissementData = [
                        'nomEtablissement' => isset($mapping['nomEtablissement']) ? strval($rowData[$mapping['nomEtablissement']] ?? '') : '',
                        'villeEtablissement' => isset($mapping['villeEtablissement']) ? strval($rowData[$mapping['villeEtablissement']] ?? '') : '',
                        'codePostalEtablissement' => isset($mapping['codePostalEtablissement']) ? strval($rowData[$mapping['codePostalEtablissement']] ?? '') : '',
                        'departementEtablissement' => isset($mapping['departementEtablissement']) ? strval($rowData[$mapping['departementEtablissement']] ?? '') : '',
                        'paysEtablissement' => isset($mapping['paysEtablissement']) ? strval($rowData[$mapping['paysEtablissement']] ?? '') : ''
                    ];

                    // Vérifier si au moins le nom et la ville sont présents
                    if (!empty($etablissementData['nomEtablissement']) || !empty($etablissementData['villeEtablissement']))
                    {
                        $etablissement = $this->getOrCreateEtablissement($etablissementModel, $etablissementData);
                    } else {
                        // Créer un établissement par défaut si les données sont manquantes
                        $etablissementData = [
                            'nomEtablissement' => 'Non renseigné',
                            'villeEtablissement' => 'Non renseigné',
                            'codePostalEtablissement' => '',
                            'departementEtablissement' => '',
                            'paysEtablissement' => ''
                        ];
                        $etablissement = $this->getOrCreateEtablissement($etablissementModel, $etablissementData);
                    }

                    // 5. Création de la relation
                    if ($etablissement && isset($etablissement['idEtablissement']))
                    {
                        // Préparer les données
                        $noteLycee = null;
                        if (isset($mapping['noteLycee']) && 
                            isset($rowData[$mapping['noteLycee']]) && 
                            is_numeric($rowData[$mapping['noteLycee']]))
                            {
                            $noteLycee = number_format(floatval($rowData[$mapping['noteLycee']]), 2, '.', '');
                        }
                        
                        $noteFicheAvenir = null;
                        if (isset($mapping['noteFicheAvenir']) && 
                            isset($rowData[$mapping['noteFicheAvenir']]) && 
                            is_numeric($rowData[$mapping['noteFicheAvenir']]))
                            {
                            $noteFicheAvenir = number_format(floatval($rowData[$mapping['noteFicheAvenir']]), 2, '.', '');
                        }

                        $etudierDansData = [
                            'numCandidat' => strval($numCandidat),
                            'anneeUniversitaire' => strval($annee),
                            'idEtablissement' => intval($etablissement['idEtablissement']),
                            'noteLycee' => $noteLycee,
                            'noteFicheAvenir' => $noteFicheAvenir
                        ];

                        // Supprimer l'ancienne relation si elle existe
                        $db->table('EtudierDans')
                        ->where('numCandidat', $numCandidat)
                        ->where('anneeUniversitaire', $annee)
                        ->where('idEtablissement', $etablissement['idEtablissement'])
                        ->delete();

                        $db->table('EtudierDans')->insert($etudierDansData);
                    }

                } catch (\Exception $e)
                {
                    continue;
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false)
            {
                return redirect()->back()->with('error', 'Erreur lors de la transaction');
            }

            if ($nbInserted === 0)
            {
                return redirect()->back()->with('error', 'Aucun candidat n\'a été importé');
            } else {
                return redirect()->to('/parcoursup')->with('success', "$nbInserted candidat(s) importé(s) avec succès");
            }

        }
        catch (\Exception $e)
        {
            return redirect()->back()->with('error', 'Une erreur est survenue pendant l\'import');
        }
    }

    private function mapColumns($headers)
    {
        $mapping = [];
        
        foreach ($headers as $idx => $col)
        {
            if (empty($col)) continue;
            
            $colLower = mb_strtolower(trim($col));

            // Cas spéciaux pour l'établissement
            $etablissementCases = [
                'nom etablissement origine' => 'nomEtablissement',
                'commune etablissement origine - libellé' => 'villeEtablissement',
                'commune etablissement origine - codepostal' => 'codePostalEtablissement', 
                'département etablissement origine' => 'departementEtablissement',
                'pays etablissement origine' => 'paysEtablissement'
            ];

            // Vérifier les colonnes d'établissement
            foreach ($etablissementCases as $pattern => $field)
            {
                if (strpos($colLower, $pattern) !== false)
                {
                    $mapping[$field] = $idx;
                    continue 2;
                }
            }

            // Cas spéciaux qui nécessitent un traitement exact
            $specialCases = [
                'candidat - code' => 'numCandidat',
                'numéro parcoursup' => 'numCandidat',
                'candidat - nom' => 'nom',
                'candidat - prénom' => 'prenom',
                'nom' => 'nom',
                'prénom' => 'prenom',
                'profil' => 'profil',
                'marqueurs dossier' => 'marqueurDossier',
                'diplôme' => 'diplome',
                'serie' => 'serie',
                'commentaires' => 'commentaire',
                'en préparation / obtenu' => 'preparation_obtenu',
                'combinaison des enseignements de spécialité en terminale' => 'specialitesTerminale',
                'civilité' => 'civilite',
                'candidat boursier - code' => 'boursier'
            ];

            // Vérifier d'abord les cas spéciaux
            foreach ($specialCases as $pattern => $field)
            {
                if ($colLower === $pattern)
                {
                    $mapping[$field] = $idx;
                    continue 2;
                }
            }

            // Pour les autres colonnes, on utilise une approche plus flexible
            foreach ($this->columnMappings as $category => $fields)
            {
                $categoryLower = mb_strtolower($category);
                
                foreach ($fields as $search => $dbField)
                {
                    $searchLower = mb_strtolower($search);
                    
                    // Construire plusieurs patterns possibles
                    $patterns = [
                        $categoryLower . ' - ' . $searchLower,
                        $categoryLower . ' ' . $searchLower,
                        $searchLower . ' ' . $categoryLower,
                        $categoryLower . '.*' . $searchLower
                    ];

                    foreach ($patterns as $pattern)
                    {
                        if (strpos($colLower, $pattern) !== false)
                        {
                            $mapping[$dbField] = $idx;
                            continue 3;
                        }
                    }
                }
            }
        }

        return $mapping;
    }

    private function getOrCreateEtablissement($model, $data)
    {
        // Chercher l'établissement existant
        $etablissement = $model->where('nomEtablissement', $data['nomEtablissement'])
                            ->where('villeEtablissement', $data['villeEtablissement'])
                            ->first();
        
        // Si non trouvé, on le crée
        if (!$etablissement)
        {
            $model->insert($data);
            $etablissement = $model->where('nomEtablissement', $data['nomEtablissement'])
                                ->where('villeEtablissement', $data['villeEtablissement'])
                                ->first();
        }
        
        return $etablissement;
    }

    private function formatBoursier($value)
    {
        if (empty($value))
        {
            return '0';
        }
        
        // Si c'est déjà un nombre
        if (is_numeric($value))
        {
            $intValue = intval($value);
            return (string)($intValue > 0 ? $intValue : 0);
        }
        
        // Si c'est une chaîne
        $value = strtolower(trim($value));
        if ($value === 'oui' || $value === '1' || $value === 'true')
        {
            return '1';
        }
        
        return '0';
    }

    public function modifierCandidat()
    {
        // Vérifier que c'est une requête AJAX
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Requête non autorisée']);
        }
        
        $json = $this->request->getJSON(true);
        
        // Validation des données
        if (!isset($json['numCandidat'], $json['anneeUniversitaire'], $json['field'], $json['value'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Données manquantes']);
        }
        
        $numCandidat = $json['numCandidat'];
        $anneeUniversitaire = $json['anneeUniversitaire'];
        $field = $json['field'];
        $value = $json['value'];
        
        // Validation du champ
        $candidatModel = new \App\Models\CandidatModel();
        $allowedFields = array_diff($candidatModel->allowedFields, ['numCandidat', 'anneeUniversitaire']);
        
        if (!in_array($field, $allowedFields)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Champ non modifiable']);
        }
        
        try {
            $db = \Config\Database::connect();
            
            // Vérifier que le candidat existe
            $candidat = $db->table('Candidat')
                ->where('numCandidat', $numCandidat)
                ->where('anneeUniversitaire', $anneeUniversitaire)
                ->get()
                ->getRow();
                
            if (!$candidat) {
                return $this->response->setJSON(['success' => false, 'message' => 'Candidat non trouvé']);
            }
            
            // Traitement spécial selon le type de champ
            $processedValue = $this->processFieldValue($field, $value);
            
            // Mise à jour
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

    private function processFieldValue($field, $value)
    {
        // Champs numériques
        $numericFields = ['noteDossier', 'noteGlobale'];
        if (in_array($field, $numericFields)) {
            if (empty($value) || !is_numeric($value)) {
                return 0;
            }
            return floatval($value);
        }
        
        // Champ boursier
        if ($field === 'boursier') {
            return $this->formatBoursier($value);
        }
        
        // Champs texte - nettoyer et limiter
        $value = trim($value);
        if (empty($value)) {
            return '-';
        }
        
        // Limiter la longueur selon le champ
        $maxLengths = [
            'nom' => 100,
            'prenom' => 100,
            'civilite' => 10,
            'profil' => 50,
            'commentaire' => 500
        ];
        
        if (isset($maxLengths[$field])) {
            $value = substr($value, 0, $maxLengths[$field]);
        }
        
        return $value;
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
            
            // Récupérer tous les filtres actifs
            $filtres = $filtreModel->where('actif', 1)->findAll();
            
            if (empty($filtres)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Aucun filtre actif trouvé']);
            }
            
            // Récupérer les candidats avec jointures
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
            $db = \Config\Database::connect();
            
            foreach ($candidats as $candidat) {
                $noteCalculee = $this->appliquerFiltres($candidat, $filtres);
                
                $result = $db->table('Candidat')
                    ->where('numCandidat', $candidat['numCandidat'])
                    ->where('anneeUniversitaire', $annee)
                    ->update(['noteDossier' => $noteCalculee]);
                
                if ($result) {
                    $nbMisAJour++;
                    $notesCalculees[$candidat['numCandidat']] = number_format($noteCalculee, 2);
                }
            }
            
            return $this->response->setJSON([
                'success' => true,
                'message' => "Notes calculées avec succès pour l'année {$annee}",
                'nbMisAJour' => $nbMisAJour,
                'notes' => $notesCalculees,
                'annee' => $annee
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Erreur serveur lors du calcul']);
        }
    }
}
