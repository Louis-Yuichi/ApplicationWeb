<?php
// filepath: /home/etudiant/dt231159/STAGE/ApplicationWeb/app/Services/ImportExportService.php

namespace App\Services;

class ImportExportService
{
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

	public function mapColumns($headers)
	{
		$mapping = [];
		
		foreach ($headers as $idx => $col) {
			if (empty($col)) continue;
			
			$colLower = mb_strtolower(trim($col));

			// Cas spéciaux pour l'établissement - VÉRIFIER EN PREMIER
			$etablissementCases = [
				'nom etablissement origine' => 'nomEtablissement',
				'commune etablissement origine - libellé' => 'villeEtablissement',
				'commune etablissement origine - codepostal' => 'codePostalEtablissement', 
				'département etablissement origine' => 'departementEtablissement',
				'pays etablissement origine' => 'paysEtablissement'
			];

			foreach ($etablissementCases as $pattern => $field) {
				if (strpos($colLower, $pattern) !== false) {
					$mapping[$field] = $idx;
					continue 2;
				}
			}

			// DÉTECTER TOUTES LES COLONNES FORMATION CANDIDATES
			$formationCandidates = [];
			
			// Variante 1: Filiere pour scolarité
			if (strpos($colLower, 'filiere') !== false && 
				strpos($colLower, 'scolarité') !== false && 
				strpos($colLower, 'libellé') !== false) {
				$formationCandidates[] = $idx;
			}
			
			// Variante 2: Formation saisie manuelle
			if (strpos($colLower, 'formation') !== false && 
				strpos($colLower, 'libellé') !== false) {
				$formationCandidates[] = $idx;
			}
			
			// Variante 3: Spécialité / Mention
			if (strpos($colLower, 'spécialité') !== false && 
				strpos($colLower, 'mention') !== false && 
				strpos($colLower, 'libellé') !== false) {
				$formationCandidates[] = $idx;
			}
			
			// Si on a trouvé des candidats formation, les stocker pour traitement ultérieur
			if (!empty($formationCandidates)) {
				// Stocker temporairement tous les candidats
				if (!isset($mapping['_formationCandidates'])) {
					$mapping['_formationCandidates'] = [];
				}
				$mapping['_formationCandidates'] = array_merge($mapping['_formationCandidates'], $formationCandidates);
			}

			// Cas spéciaux exacts (SAUF formation qui sera traitée après)
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
				'commentaire' => 'commentaire',
				'en préparation / obtenu' => 'preparation_obtenu',
				'combinaison des enseignements de spécialité en terminale' => 'specialitesTerminale',
				'enseignement de spécialité abandonné en première' => 'specialiteAbandonne',
				'civilité' => 'civilite',
				'candidat boursier - code' => 'boursier',
				'scolarité' => 'scolarite',
				'type diplôme - code' => 'typeDiplomeCode',
				'série - code' => 'serieCode',
				'spécialité mention' => 'specialiteMention',
				'note lycée' => 'noteLycee',
				'note fiche avenir' => 'noteFicheAvenir',
				'note dossier' => 'noteDossier',
				'note globale' => 'noteGlobale'
			];

			foreach ($specialCases as $pattern => $field) {
				if ($colLower === $pattern) {
					$mapping[$field] = $idx;
					continue 2;
				}
			}

			// Mapping flexible pour les autres cas (SAUF formation)
			foreach ($this->columnMappings as $category => $fields) {
				// IGNORER la catégorie formation ici
				if ($category === 'formation') continue;
				
				$categoryLower = mb_strtolower($category);
				
				foreach ($fields as $search => $dbField) {
					$searchLower = mb_strtolower($search);
					
					// Construire plusieurs patterns possibles
					$patterns = [
						$categoryLower . ' - ' . $searchLower,
						$categoryLower . ' ' . $searchLower,
						$searchLower . ' ' . $categoryLower,
						$categoryLower . '.*' . $searchLower
					];

					foreach ($patterns as $pattern) {
						if (strpos($colLower, $pattern) !== false) {
							$mapping[$dbField] = $idx;
							continue 3;
						}
					}
				}
			}
		}

		// NETTOYER la liste des candidats formation (dédoublonner)
		if (isset($mapping['_formationCandidates'])) {
			$mapping['_formationCandidates'] = array_unique($mapping['_formationCandidates']);
		}

		return $mapping;
	}

    public function formatBoursier($value)
    {
        if (empty($value)) {
            return '0';
        }
        
        // Si c'est déjà un nombre
        if (is_numeric($value)) {
            $intValue = intval($value);
            return (string)($intValue > 0 ? $intValue : 0);
        }
        
        // Si c'est une chaîne
        $value = strtolower(trim($value));
        if ($value === 'oui' || $value === '1' || $value === 'true') {
            return '1';
        }
        
        return '0';
    }

    public function processFieldValue($field, $value)
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

    public function generateCSV($data, $headers, $filename)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, $headers);
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
    }

    public function getOrCreateEtablissement($model, $data)
    {
        // Chercher l'établissement existant
        $etablissement = $model->where('nomEtablissement', $data['nomEtablissement'])
                              ->where('villeEtablissement', $data['villeEtablissement'])
                              ->first();
        
        // Si non trouvé, on le crée
        if (!$etablissement) {
            $model->insert($data);
            $etablissement = $model->where('nomEtablissement', $data['nomEtablissement'])
                                  ->where('villeEtablissement', $data['villeEtablissement'])
                                  ->first();
        }
        
        return $etablissement;
    }

    // choisir la colonne formation avec données
    public function chooseFormationColumn($headers, $mapping, $rowData)
    {
        // Si pas de candidats formation détectés, retourner null
        if (!isset($mapping['_formationCandidates']) || empty($mapping['_formationCandidates'])) {
            return null;
        }
        
        // Parcourir les candidats et prendre le premier qui a des données
        foreach ($mapping['_formationCandidates'] as $idx) {
            if (isset($rowData[$idx]) && !empty(trim($rowData[$idx]))) {
                return $idx;
            }
        }
        
        // Si aucune colonne n'a de données, retourner null
        return null;
    }
}
