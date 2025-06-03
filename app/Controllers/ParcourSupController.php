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
		$model = new \App\Models\CandidatModel();

		$candidats = $model->findAll();
		$fields    = $model->allowedFields;

		$data['candidats'] = $candidats;
		$data['fields'   ] = $fields;

		$this->view('parcoursup/gestion.html.twig', $data);
	}

	public function importer()

	{
		helper(['form', 'filesystem']);
		
		try
		{
			$file = $this->request->getFile('fichier');
			$annee = $this->request->getPost('annee');
			
			if (!$file)
			{
				log_message('error', 'Aucun fichier reçu');
				return redirect()->back()->with('error', 'Aucun fichier reçu');
			}

			// Vérifie l'extension
			if (!$file->isValid() || $file->getClientExtension() !== 'xlsx')
			{
				log_message('error', 'Extension invalide: ' . $file->getClientExtension());
				return redirect()->back()->with('error', 'Seuls les fichiers .xlsx sont acceptés.');
			}

			// Lecture du fichier
			$spreadsheet = IOFactory::load($file->getTempName());
			$sheet = $spreadsheet->getActiveSheet();
			$header = $sheet->rangeToArray('A1:W1')[0];
			
			// Nettoyer les en-têtes en supprimant les valeurs null
			$header = array_filter($header, function($value)
			{
				return $value !== null;
			});
			
			log_message('info', 'En-têtes trouvés: ' . json_encode($header));

			// Vérifie les colonnes
			if ($header !== $this->colonnesAttendues)
			{
				log_message('error', 'Colonnes invalides. Attendues: ' . json_encode($this->colonnesAttendues) . ' Reçues: ' . json_encode($header));
				return redirect()->back()->with('error', 'Les colonnes du fichier ne correspondent pas au format attendu.');
			}

			$model = new \App\Models\CandidatModel();
			$nbInserted = 0;

			// Parcours des lignes
			foreach ($sheet->getRowIterator(2) as $row)
			{
				$rowData = $sheet->rangeToArray('A' . $row->getRowIndex() . ':W' . $row->getRowIndex())[0];

				// Arrête la boucle si la ligne est totalement vide
				if (count(array_filter($rowData, function($v) { return $v !== null && $v !== ''; })) === 0) {
					break;
				}

				// Associe les colonnes aux champs de la base
				$data = [
					'numCandidat'          => $rowData[0],
					'anneeUniversitaire'   => $annee,
					'nom'                  => $rowData[1],
					'prenom'               => $rowData[2],
					'profil'               => $rowData[3] ?? '',
					'groupe'               => null,
					'marqueurDossier'      => $rowData[4] ?? '',
					'scolarite'            => $rowData[5] ?? '',
					'diplome'              => $rowData[6] ?? '',
					'preparation_obtenu'   => $rowData[7] ?? '', 
					'serie'                => $rowData[8] ?? '',
					'specialitesTerminale' => $rowData[9] ?? '',
					'specialiteAbandonne'  => $rowData[10] ?? '',
					'noteLycee'            => is_numeric($rowData[19]) ? $rowData[19] : null,
					'noteFicheAvenir'      => is_numeric($rowData[18]) ? $rowData[18] : null,
					'noteDossier'          => is_numeric($rowData[20]) ? $rowData[20] : null,
					'commentaire'          => $rowData[21] ?? ''
				];

				// Vérifie les champs obligatoires
				if (empty($data['numCandidat']) || empty($data['nom']) || empty($data['prenom']))
				{
					log_message('error', 'Ligne ignorée (champs obligatoires manquants) : ' . json_encode($rowData));
					continue;
				}

				// Ajoutez cette vérification avant l'insertion
				if (empty($rowData[0]))
				{
					log_message('error', 'Ligne ignorée (numéro candidat manquant) : ' . json_encode($rowData));
					continue;
				}

				try
				{
					// Vérifie si le candidat existe déjà
					$existingCandidat = $model->where('numCandidat', $data['numCandidat'])
						->where('anneeUniversitaire', $data['anneeUniversitaire'])
						->first();

					if ($existingCandidat)
					{
						// Met à jour le candidat existant
						if ($model->update(['numCandidat' => $data['numCandidat']], $data))
						{
							$nbInserted++;
						}
						else
						{
							log_message('error', 'Erreur mise à jour ligne ' . $row->getRowIndex() . ': ' . json_encode($model->errors()));
						}
					}
					else
					{
						// Insère un nouveau candidat
						if ($model->insert($data))
						{
							$nbInserted++;
						}
						else
						{
							log_message('error', 'Erreur insertion ligne ' . $row->getRowIndex() . ': ' . json_encode($model->errors()));
						}
					}
				}
				catch (\Exception $e)
				{
					log_message('error', 'Exception ligne ' . $row->getRowIndex() . ': ' . $e->getMessage());
					continue;
				}
			}

			if ($nbInserted > 0)
			{
				return redirect()
					->to('/parcoursup')
					->with('success', "Import réussi ! $nbInserted candidats importés.");
			}
			else
			{
				return redirect()
					->to('/parcoursup')
					->with('error', 'Aucun candidat importé.');
			}

		}
		catch (\Exception $e)
		{
			log_message('error', 'Exception globale: ' . $e->getMessage());
			return redirect()->back()->with('error', 'Une erreur est survenue pendant l\'import.');
		}
	}
}
