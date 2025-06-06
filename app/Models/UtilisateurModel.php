<?php

namespace App\Models;

use CodeIgniter\Model;

class UtilisateurModel extends Model
{
	protected $table            = 'UtilisateurWeb';
	protected $primaryKey       = 'idUtilisateur';
	protected $allowedFields    = ['nomUtilisateur', 'prenomUtilisateur', 'mailUtilisateur', 'mdpUtilisateur'];
	protected $useAutoIncrement = true;
	protected $returnType       = 'array';

	protected $validationRules =
	[
		'nomUtilisateur'    => 'required|min_length[2]|max_length[50]',
		'prenomUtilisateur' => 'required|min_length[2]|max_length[50]',
		'mailUtilisateur'   => 'required|valid_email|is_unique[UtilisateurWeb.mailUtilisateur]',
		'mdpUtilisateur'    => 'required|min_length[6]'
	];
}