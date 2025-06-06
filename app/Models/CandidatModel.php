<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidatModel extends Model
{
	protected $table = 'Candidat';
	protected $primaryKey = 'numCandidat';
	protected $useAutoIncrement = false;
	protected $returnType = 'array';
	protected $allowedFields = [
		'numCandidat',
		'anneeUniversitaire',
		'nom',
		'prenom',
		'profil',
		'groupe',
		'marqueurDossier',
		'scolarite',
		'diplome',
		'preparation_obtenu',
		'serie',
		'specialitesTerminale',
		'specialiteAbandonne',
	//	'noteLycee',
	//	'noteFicheAvenir',
		'noteDossier',
		'commentaire'
	];

	// Relation avec Etablissement via EtudierDans
	public function etablissements()
	{
		return $this->belongsToMany(
			'App\Models\EtablissementModel',
			'EtudierDans',
			'numCandidat',
			'idEtablissement',
			'numCandidat',
			'idEtablissement'
		);
	}

	public function getEtablissementNotes($numCandidat, $idEtablissement)
	{
		$db = \Config\Database::connect();
		return $db->table('EtudierDans')
				->where('numCandidat', $numCandidat)
				->where('idEtablissement', $idEtablissement)
				->get()
				->getRow();
	}
}
