<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ParcourSupController extends BaseController
{
	protected $colonnesAttendues = [
		'Numéro ParcourSup', 'Nom', 'Prénom', 'Profil', 'Marqueurs Dossier', 'Scolarité 2023/2024', 'Diplôme',
		'En Préparation / Obtenu', 'Serie', 'Combinaison des enseignements de spécialité en Terminale',
		'Enseignement De spécialité abandonné en Première', 'Spécialité Bac Pro ou Bac avant 2021',
		"Lycée d'origine", 'Colonne1', 'Colonne2', 'Colonne3', 'Colonne4',
		'Note Globale Calculée', 'Note Fiche Avenir', 'Note Lycée', 'Note Dossier', 'Commentaire'
	];

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
		$columnsConfig = [
			'candidat' => array_diff($candidatModel->allowedFields, ['noteDossier', 'commentaire']),
			'etablissement' => $etablissementModel->allowedFields,
			'etudierDans' => array_diff($etudierDansModel->allowedFields, ['numCandidat', 'idEtablissement']),
			'candidat_fin' => ['noteDossier', 'commentaire']
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
			'columnsConfig' => $columnsConfig
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
			
			$nbInserted = 0;
			$db = \Config\Database::connect();
			$db->transStart();

			// Initialisation des modèles
			$etablissementModel = new \App\Models\EtablissementModel();
			$candidatModel = new \App\Models\CandidatModel();
			$etudierDansModel = new \App\Models\EtudierDansModel();

			foreach ($sheet->getRowIterator(2) as $row) {
				$rowData = $sheet->rangeToArray('A' . $row->getRowIndex() . ':U' . $row->getRowIndex())[0];
				if (empty(array_filter($rowData))) continue;

				// Gestion de l'établissement
				$etablissementData = [
					'nomEtablissement'         => $rowData[12] ?? '',
					'villeEtablissement'       => $rowData[13] ?? '',
					'codePostalEtablissement'  => $rowData[14] ?? '',
					'departementEtablissement' => $rowData[15] ?? '',
					'paysEtablissement'        => $rowData[16] ?? ''
				];

				$etablissement = $etablissementModel->firstOrCreate($etablissementData);

				// Gestion du candidat
				$candidatModel->replace([
					'numCandidat'           => $rowData[0],
					'anneeUniversitaire'    => $annee,
					'nom'                   => $rowData[1],
					'prenom'                => $rowData[2],
					'profil'                => $rowData[3]  ?? '',
					'marqueurDossier'       => $rowData[4]  ?? '',
					'scolarite'             => $rowData[5]  ?? '',
					'diplome'               => $rowData[6]  ?? '',
					'preparation_obtenu'    => $rowData[7]  ?? '',
					'serie'                 => $rowData[8]  ?? '',
					'specialitesTerminale'  => $rowData[9]  ?? '',
					'specialiteAbandonne'   => $rowData[10] ?? '',
					'noteDossier'           => is_numeric($rowData[19]) ? $rowData[19] : null
				]);

				// Gestion de la relation
				$etudierDansModel->replace([
					'numCandidat'       => $rowData[0],
					'idEtablissement'   => $etablissement['idEtablissement'],
					'noteLycee'         => is_numeric($rowData[18]) ? $rowData[18] : null,
					'noteFicheAvenir'   => is_numeric($rowData[17]) ? $rowData[17] : null
				]);

				$nbInserted++;
			}

			$db->transComplete();

			if ($db->transStatus() === false) {
				return redirect()->back()->with('error', 'Erreur lors de la transaction');
			}

			return redirect()->to('/parcoursup')->with('success', "Import réussi ! $nbInserted candidats importés.");

		} catch (\Exception $e) {
			log_message('error', 'Erreur globale: ' . $e->getMessage());
			return redirect()->back()->with('error', 'Une erreur est survenue pendant l\'import');
		}
	}
}
