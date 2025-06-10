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

	public function gestion()
	{
		// Récupération des modèles
		$candidatModel = new \App\Models\CandidatModel();
		$etablissementModel = new \App\Models\EtablissementModel();
		$etudierDansModel = new \App\Models\EtudierDansModel();

		// Récupération des champs dans l'ordre souhaité
		$columns = [
			'Candidat' => array_diff($candidatModel->allowedFields, ['noteDossier', 'commentaire']),
			'Etablissement' => $etablissementModel->allowedFields,
			'EtudierDans' => array_values(array_diff($etudierDansModel->allowedFields, ['numCandidat', 'idEtablissement'])),
			'Candidat_fin' => ['noteDossier', 'commentaire']
		];

		// Récupération des données avec les jointures
		$candidats = $candidatModel->select('
			Candidat.*,
			Etablissement.*,
			EtudierDans.noteLycee,
			EtudierDans.noteFicheAvenir
		')
		->join('EtudierDans', 'EtudierDans.numCandidat = Candidat.numCandidat')
		->join('Etablissement', 'Etablissement.idEtablissement = EtudierDans.idEtablissement')
		->findAll();

		return $this->view('parcoursup/gestion.html.twig', [
			'candidats' => $candidats,
			'columns' => $columns
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
			$mapping = [];
			foreach ($header as $idx => $col)
			{
				$colLower = mb_strtolower($col);

				// Informations Candidat
				if (strpos($colLower, 'candidat') !== false)
				{
					if     (strpos($colLower, 'code')   !== false)   $mapping['numCandidat']       = $idx;
					elseif (strpos($colLower, 'nom')    !== false && !strpos($colLower, 'prenom') !== false)   $mapping['nom']               = $idx;
					elseif (strpos($colLower, 'prénom') !== false || strpos($colLower, 'prenom') !== false)   $mapping['prenom']            = $idx;  // Changé de 'prénom' à 'prenom'
				}

				// Civilité et profil
				if (strpos($colLower, 'civilité')        !== false
					|| strpos($colLower, 'civilite')     !== false)  $mapping['civilite']          = $idx;
				if (strpos($colLower, 'profil')          !== false
					&& strpos($colLower, 'libellé')      !== false)  $mapping['profil']            = $idx;
				if (strpos($colLower, 'boursier')        !== false)  $mapping['boursier']          = $idx;

				// Scolarité
				if (strpos($colLower, 'filiere')         !== false)  $mapping['scolarite']         = $idx;
				if (strpos($colLower, 'formation')       !== false)  $mapping['formation']         = $idx;
				if (strpos($colLower, 'spécialité')      !== false
					|| strpos($colLower, 'specialite')   !== false)
				{
					if     (strpos($colLower, 'mention') !== false)  $mapping['specialiteMention'] = $idx;
					elseif (strpos($colLower, 'libellé') !== false)  $mapping['specialite']        = $idx;
				}

				// Diplôme
				if (strpos($colLower, 'type diplôme')     !== false
					|| strpos($colLower, 'type diplome')  !== false)
				{
					if     (strpos($colLower, 'code')     !== false) $mapping['typeDiplomeCode']   = $idx;
					elseif (strpos($colLower, 'libellé')  !== false) $mapping['typeDiplome']       = $idx;
				}
				if (strpos($colLower, 'série diplôme')    !== false
					|| strpos($colLower, 'serie diplome') !== false)
				{
					if     (strpos($colLower, 'code')     !== false) $mapping['serieCode']         = $idx;
					elseif (strpos($colLower, 'libellé')  !== false) $mapping['serie']             = $idx;
				}

				// Spécialités
				if (strpos($colLower, 'combinaison des enseignements') !== false)
					$mapping['specialitesTerminale'] = $idx;
				if (strpos($colLower, 'abandonné en première')  !== false)
					$mapping['specialiteAbandonne']  = $idx;

				// Établissement
				if (strpos($colLower, 'établissement')          !== false
					|| strpos($colLower, 'etablissement')       !== false)
				{
					if     (strpos($colLower, 'nom')            !== false) $mapping['nomEtablissement']         = $idx;
					elseif (strpos($colLower, 'commune')        !== false)
					{
						if     (strpos($colLower, 'libellé')    !== false) $mapping['villeEtablissement']       = $idx;
						elseif (strpos($colLower, 'codepostal') !== false) $mapping['codePostalEtablissement']  = $idx;
					}
					elseif (strpos($colLower, 'département')    !== false) $mapping['departementEtablissement'] = $idx;
					elseif (strpos($colLower, 'pays')           !== false) $mapping['paysEtablissement']        = $idx;
				}

				// Notes
				if (strpos($colLower, 'note') !== false)
				{
					if     (strpos($colLower, 'globale')      !== false) $mapping['noteGlobale']     = $idx;
					elseif (strpos($colLower, 'fiche avenir') !== false) $mapping['noteFicheAvenir'] = $idx;
					elseif (strpos($colLower, 'lycée')        !== false) $mapping['noteLycee']       = $idx;
					elseif (strpos($colLower, 'dossier')      !== false) $mapping['noteDossier']     = $idx;
				}

				// Commentaire
				if (strpos($colLower, 'commentaire') !== false) $mapping['commentaire'] = $idx;
			}

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

                $candidatData = [
					'numCandidat'          => $rowData[$mapping['numCandidat']] ?? '',
					'anneeUniversitaire'   => $annee,
					'nom'                  => $rowData[$mapping['nom']] ?? '',
					'prenom'               => $rowData[$mapping['prenom']] ?? '',
					'civilite'             => $rowData[$mapping['civilite']] ?? '',
					'profil'               => $rowData[$mapping['profil']] ?? '',
					'groupe'               => $rowData[$mapping['boursier']] ?? '',
					'boursier'             => $rowData[$mapping['boursier']] ?? '',
					'scolarite'            => $rowData[$mapping['scolarite']] ?? '',
					'formation'            => $rowData[$mapping['formation']] ?? '',
					'diplome'              => $rowData[$mapping['typeDiplome']] ?? '',
					'typeDiplomeCode'      => $rowData[$mapping['typeDiplomeCode']] ?? '',
					'serie'                => $rowData[$mapping['serie']] ?? '',
					'serieCode'            => $rowData[$mapping['serieCode']] ?? '',
					'specialitesTerminale' => $rowData[$mapping['specialitesTerminale']] ?? '',
					'specialiteAbandonne'  => $rowData[$mapping['specialiteAbandonne']] ?? '',
					'specialiteMention'    => $rowData[$mapping['specialiteMention']] ?? '',
					'noteDossier'          => is_numeric($rowData[$mapping['noteDossier']] ?? '') ? $rowData[$mapping['noteDossier']] : null,
					'noteGlobale'          => is_numeric($rowData[$mapping['noteGlobale']] ?? '') ? $rowData[$mapping['noteGlobale']] : null,
					'commentaire'          => $rowData[$mapping['commentaire']] ?? '',
					// ...autres champs...
				];

				// Gestion de l'établissement
				$etablissementData = [
					'nomEtablissement'         => $rowData[$mapping['nomEtablissement']] ?? '',
					'villeEtablissement'       => $rowData[$mapping['villeEtablissement']] ?? '',
					'codePostalEtablissement'  => $rowData[$mapping['codePostalEtablissement']] ?? '',
					'departementEtablissement' => $rowData[$mapping['departementEtablissement']] ?? '',
					'paysEtablissement'        => $rowData[$mapping['paysEtablissement']] ?? ''
				];

				$etablissement = $etablissementModel->firstOrCreate($etablissementData);
                $candidatModel->replace($candidatData);
                
                // Gestion de la relation
                $etudierDansModel->replace([
                    'numCandidat'       => $rowData[$mapping['numCandidat']] ?? '',
                    'idEtablissement'   => $etablissement['idEtablissement'],
                    'noteLycee'         => is_numeric($rowData[$mapping['noteLycee']] ?? '') ? $rowData[$mapping['noteLycee']] : null,
                    'noteFicheAvenir'   => is_numeric($rowData[$mapping['noteFicheAvenir']] ?? '') ? $rowData[$mapping['noteFicheAvenir']] : null
                ]);

                $nbInserted++; // Incrémentation du compteur
            }

            $db->transComplete();

			if ($db->transStatus() === false)
			{
				return redirect()->back()->with('error', 'Erreur lors de la transaction');
			}

			return redirect()->to('/parcoursup')->with('success', "Import réussi ! $nbInserted candidats importés.");

		}
		catch (\Exception $e)
		{
			log_message('error', 'Erreur globale: ' . $e->getMessage());
			return redirect()->back()->with('error', 'Une erreur est survenue pendant l\'import');
		}
	}
}
