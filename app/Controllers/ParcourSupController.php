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
    
    // Récupérer tous les filtres actifs
    $filtres = $filtreModel->where('actif', 1)->findAll();
    
    if (empty($filtres)) {
        return redirect()->to('/filtres')->with('error', 'Aucun filtre actif trouvé');
    }
    
    // CORRECTION: Récupérer avec les jointures pour avoir noteLycee
    $candidats = $candidatModel->select('
            Candidat.*,
            Etablissement.*,
            EtudierDans.noteLycee,
            EtudierDans.noteFicheAvenir
        ')
        ->join('EtudierDans', 'EtudierDans.numCandidat = Candidat.numCandidat AND EtudierDans.anneeUniversitaire = Candidat.anneeUniversitaire')
        ->join('Etablissement', 'Etablissement.idEtablissement = EtudierDans.idEtablissement')
        ->findAll();
    
    $nbMisAJour = 0;
    
    foreach ($candidats as $candidat) {
        $noteCalculee = $this->appliquerFiltres($candidat, $filtres);
        
        // Mettre à jour la note dossier
        $candidatModel->update([
            'numCandidat' => $candidat['numCandidat'],
            'anneeUniversitaire' => $candidat['anneeUniversitaire']
        ], [
            'noteDossier' => $noteCalculee
        ]);
        
        $nbMisAJour++;
    }
    
    return redirect()->to('/filtres');
}

	private function appliquerFiltres($candidat, $filtres)
	{
		// CORRECTION: Utiliser noteLycee comme note de base, sinon 10
		$noteBase = 10; // Valeur par défaut
		
		if (isset($candidat['noteLycee']) && !empty($candidat['noteLycee']) && is_numeric($candidat['noteLycee'])) {
			$noteBase = floatval($candidat['noteLycee']);
			log_message('debug', "Note de base (noteLycee) pour candidat {$candidat['numCandidat']}: {$noteBase}");
		} else {
			log_message('debug', "Pas de noteLycee pour candidat {$candidat['numCandidat']}, utilisation de la note par défaut: {$noteBase}");
		}
		
		$noteFinale = $noteBase;
		
		foreach ($filtres as $filtre) {
			if ($this->evaluerCondition($candidat, $filtre)) {
				log_message('debug', "Filtre '{$filtre['nomFiltre']}' appliqué au candidat {$candidat['numCandidat']}");
				
				switch ($filtre['typeAction']) {
					case 'bonus':
						$noteFinale += $filtre['valeurAction'];
						log_message('debug', "Bonus +{$filtre['valeurAction']}: {$noteFinale}");
						break;
					case 'malus':
						$noteFinale -= abs($filtre['valeurAction']);
						log_message('debug', "Malus -{$filtre['valeurAction']}: {$noteFinale}");
						break;
					case 'coefficient':
						$noteFinale *= $filtre['valeurAction'];
						log_message('debug', "Coefficient x{$filtre['valeurAction']}: {$noteFinale}");
						break;
					case 'note_directe':
						$noteFinale = $filtre['valeurAction'];
						log_message('debug', "Note directe = {$filtre['valeurAction']}: {$noteFinale}");
						break;
				}
			} else {
				log_message('debug', "Filtre '{$filtre['nomFiltre']}' PAS appliqué au candidat {$candidat['numCandidat']} (condition non remplie)");
			}
		}
		
		// S'assurer que la note reste dans les limites (0-20)
		$noteFinale = max(0, min(20, $noteFinale));
		
		log_message('debug', "Note finale pour candidat {$candidat['numCandidat']}: {$noteFinale}");
		
		return $noteFinale;
	}

	private function evaluerCondition($candidat, $filtre)
	{
		$valeurCandidat = $candidat[$filtre['colonneSource']] ?? '';
		$valeurCondition = $filtre['valeurCondition'];
		
		// Log détaillé pour debug
		log_message('debug', "=== EVALUATION CONDITION ===");
		log_message('debug', "Filtre: {$filtre['nomFiltre']}");
		log_message('debug', "Colonne: {$filtre['colonneSource']}");
		log_message('debug', "Valeur candidat: '{$valeurCandidat}' (type: " . gettype($valeurCandidat) . ")");
		log_message('debug', "Valeur condition: '{$valeurCondition}' (type: " . gettype($valeurCondition) . ")");
		log_message('debug', "Type condition: {$filtre['conditionType']}");
		
		$result = false;
		
		switch ($filtre['conditionType']) {
			case 'contient':
				$result = stripos($valeurCandidat, $valeurCondition) !== false;
				log_message('debug', "Test 'contient': stripos('{$valeurCandidat}', '{$valeurCondition}') = " . (stripos($valeurCandidat, $valeurCondition) === false ? 'false' : stripos($valeurCandidat, $valeurCondition)));
				break;
			case 'egal':
				$result = strcasecmp($valeurCandidat, $valeurCondition) === 0;
				log_message('debug', "Test 'egal': strcasecmp('{$valeurCandidat}', '{$valeurCondition}') = " . strcasecmp($valeurCandidat, $valeurCondition));
				break;
			case 'different':
				$result = strcasecmp($valeurCandidat, $valeurCondition) !== 0;
				log_message('debug', "Test 'different': strcasecmp('{$valeurCandidat}', '{$valeurCondition}') = " . strcasecmp($valeurCandidat, $valeurCondition));
				break;
			case 'commence_par':
				$result = stripos($valeurCandidat, $valeurCondition) === 0;
				log_message('debug', "Test 'commence_par': stripos('{$valeurCandidat}', '{$valeurCondition}') = " . (stripos($valeurCandidat, $valeurCondition) === false ? 'false' : stripos($valeurCandidat, $valeurCondition)));
				break;
			case 'finit_par':
				$result = strlen($valeurCondition) <= strlen($valeurCandidat) && 
					   strcasecmp(substr($valeurCandidat, -strlen($valeurCondition)), $valeurCondition) === 0;
				log_message('debug', "Test 'finit_par': substr('{$valeurCandidat}', -" . strlen($valeurCondition) . ") = '" . substr($valeurCandidat, -strlen($valeurCondition)) . "'");
				break;
			default:
				$result = false;
				log_message('debug', "Type de condition non reconnu: {$filtre['conditionType']}");
		}
		
		log_message('debug', "RESULTAT FINAL: " . ($result ? 'TRUE' : 'FALSE'));
		log_message('debug', "=== FIN EVALUATION ===");
		
		return $result;
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
		
		// Log pour debug
		log_message('debug', 'Année sélectionnée: ' . ($anneeSelectionnee ?? 'null'));
		
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
				
				log_message('debug', 'Nombre de candidats trouvés: ' . count($candidats));
			} catch (\Exception $e) {
				log_message('error', 'Erreur requête candidats: ' . $e->getMessage());
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
					log_message('error', 'Champ manquant: ' . $field);
				}
			}

			if (!empty($missingFields))
			{
				throw new \Exception('Champs obligatoires manquants : ' . implode(', ', $missingFields));
			}

			log_message('debug', 'Mapping final: ' . print_r($mapping, true));

			// Initialisation des modèles
			$etablissementModel = new \App\Models\EtablissementModel();
			$candidatModel = new \App\Models\CandidatModel();
			$etudierDansModel = new \App\Models\EtudierDansModel();

			$db = \Config\Database::connect();
			$db->transStart();
			
			$nbInserted = 0; // Initialisation du compteur

			foreach ($sheet->getRowIterator(2) as $row)
			{
				$rowData = $sheet->rangeToArray('A' . $row->getRowIndex() . ':' . $sheet->getHighestColumn() . $row->getRowIndex())[0];
				if (empty(array_filter($rowData))) continue;

				try {
					// 1. Vérification et préparation du numCandidat
					$numCandidat = strval($rowData[$mapping['numCandidat']] ?? ''); // Conversion explicite en string
					if (empty($numCandidat))
					{
						log_message('error', 'numCandidat vide à la ligne ' . $row->getRowIndex());
						continue;
					}
					
					// Nettoyage du numCandidat (enlever les espaces et caractères spéciaux si nécessaire)
					$numCandidat = trim($numCandidat);
					
					// Log pour vérifier la valeur
					log_message('debug', 'numCandidat traité: ' . $numCandidat);

					// 2. Préparation des données du candidat de base (champs obligatoires)
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
						$candidatData[$field] = 0;  // Garder 0 pour les champs numériques
						if (isset($mapping[$field]) && 
							isset($rowData[$mapping[$field]]) && 
							!empty($rowData[$mapping[$field]]) && 
							is_numeric($rowData[$mapping[$field]]))
							{
							$candidatData[$field] = floatval($rowData[$mapping[$field]]);
						}
					}

					// Log des données avant insertion
					log_message('debug', 'Données préparées pour insertion : ' . print_r($candidatData, true));

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
						log_message('error', 'Erreur insertion/update: ' . $e->getMessage());
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
						log_message('warning', 'Données établissement manquantes ligne ' . $row->getRowIndex() . ' - Utilisation établissement par défaut');
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
							'anneeUniversitaire' => strval($annee), // Ajouter l'année
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
					log_message('error', 'Erreur ligne ' . $row->getRowIndex() . ': ' . $e->getMessage());
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
			log_message('error', 'Erreur globale: ' . $e->getMessage());
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
			log_message('debug', 'Colonne en cours: ' . $colLower);

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
					log_message('debug', "Mapping établissement trouvé: {$colLower} -> {$field} (index: {$idx})");
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
					log_message('debug', "Mapping spécial trouvé: {$colLower} -> {$field} (index: {$idx})");
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
							log_message('debug', "Mapping trouvé: {$colLower} -> {$dbField} (index: {$idx})");
							continue 3;  // Sort des 3 boucles une fois le mapping trouvé
						}
					}
				}
			}
		}

		// Log des colonnes non mappées pour le débogage
		$unmappedColumns = [];
foreach ($headers as $idx => $header) {
    if ($header !== null && !in_array($idx, $mapping)) {
        $unmappedColumns[$idx] = mb_strtolower($header);
    }
}

		if (!empty($unmappedColumns))
		{
			log_message('warning', 'Colonnes non mappées: ' . print_r($unmappedColumns, true));
		}

		log_message('debug', 'Mapping final complet: ' . print_r($mapping, true));
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
        log_message('error', 'Erreur modification candidat: ' . $e->getMessage());
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
    // Vérifier que c'est une requête AJAX
    if (!$this->request->isAJAX()) {
        return $this->response->setJSON(['success' => false, 'message' => 'Requête non autorisée']);
    }
    
    try {
        $json = $this->request->getJSON(true);
        $annee = $json['annee'] ?? '';
        
        if (empty($annee)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Année non spécifiée']);
        }
        
        log_message('debug', "Calcul des notes pour l'année: {$annee}");
        
        $filtreModel = new \App\Models\FiltreModel();
        $candidatModel = new \App\Models\CandidatModel();
        
        // Récupérer tous les filtres actifs
        $filtres = $filtreModel->where('actif', 1)->findAll();
        
        if (empty($filtres)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Aucun filtre actif trouvé']);
        }
        
        log_message('debug', "Nombre de filtres actifs: " . count($filtres));
        
        // CORRECTION: Récupérer les candidats AVEC les jointures pour avoir noteLycee
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
        
        log_message('debug', "Nombre de candidats trouvés pour {$annee}: " . count($candidats));
        
        $nbMisAJour = 0;
        $notesCalculees = [];
        $db = \Config\Database::connect();
        
        foreach ($candidats as $candidat) {
            // CORRECTION: Passer la noteLycee à la méthode
            $noteCalculee = $this->appliquerFiltres($candidat, $filtres);
            
            // Log pour debug
            log_message('debug', "Candidat {$candidat['numCandidat']}: noteLycee = " . ($candidat['noteLycee'] ?? 'null') . ", noteCalculee = {$noteCalculee}");
            
            // Mettre à jour la note dossier dans la base UNIQUEMENT pour cette année
            $result = $db->table('Candidat')
                ->where('numCandidat', $candidat['numCandidat'])
                ->where('anneeUniversitaire', $annee)
                ->update(['noteDossier' => $noteCalculee]);
            
            if ($result) {
                $nbMisAJour++;
                $notesCalculees[$candidat['numCandidat']] = number_format($noteCalculee, 2);
            }
        }
        
        log_message('debug', "Nombre de candidats mis à jour: {$nbMisAJour}");
        
        return $this->response->setJSON([
            'success' => true,
            'message' => "Notes calculées avec succès pour l'année {$annee}",
            'nbMisAJour' => $nbMisAJour,
            'notes' => $notesCalculees,
            'annee' => $annee
        ]);
        
    } catch (\Exception $e) {
        log_message('error', 'Erreur calcul notes AJAX: ' . $e->getMessage());
        return $this->response->setJSON(['success' => false, 'message' => 'Erreur serveur lors du calcul: ' . $e->getMessage()]);
    }
}
}
