<?php

namespace App\Models;

use CodeIgniter\Model;

class EtablissementModel extends Model
{
	protected $table = 'Etablissement';
	protected $primaryKey = 'idEtablissement';
	protected $useAutoIncrement = true;
	protected $returnType = 'array';
	protected $allowedFields = [
		'nomEtablissement',
		'villeEtablissement',
		'codePostalEtablissement',
		'departementEtablissement',
		'paysEtablissement'
	];

	protected $validationRules = [
		'nomEtablissement'           => 'required|min_length[2]|max_length[255]',
		'villeEtablissement'         => 'required|min_length[2]|max_length[255]',
		'codePostalEtablissement'    => 'required|exact_length[5]',
		'departementEtablissement'   => 'required|min_length[2]|max_length[255]',
		'paysEtablissement'          => 'required|min_length[2]|max_length[255]'
	];

	public function firstOrCreate(array $data)
	{
		$etablissement = $this->where([
			'nomEtablissement' => $data['nomEtablissement'],
			'villeEtablissement' => $data['villeEtablissement']
		])->first();

		if (!$etablissement) {
			$id = $this->insert($data, true);
			return $this->find($id);
		}

		return $etablissement;
	}
}
