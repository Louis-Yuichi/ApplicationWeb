<?php

namespace App\Models;

use CodeIgniter\Model;

class EtudierDansModel extends Model
{
	protected $table = 'EtudierDans';
	protected $primaryKey = ['numCandidat', 'idEtablissement'];
	protected $useAutoIncrement = false;
	protected $returnType = 'array';
	protected $allowedFields = [
		'numCandidat',
		'idEtablissement',
		'noteLycee',
		'noteFicheAvenir'
	];

	protected $validationRules = [
		'numCandidat'     => 'required|exists[candidat.numCandidat]',
		'idEtablissement' => 'required|exists[Etablissement.idEtablissement]',
		'noteLycee'       => 'permit_empty|decimal',
		'noteFicheAvenir' => 'permit_empty|decimal'
	];
}
