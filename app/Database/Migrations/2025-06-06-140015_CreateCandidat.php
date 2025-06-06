<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCandidat extends Migration
{
	public function up()
	{
		$this->forge->addField([
			'numCandidat'         => ['type' => 'VARCHAR', 'constraint' => 20],
			'anneeUniversitaire'  => ['type' => 'VARCHAR', 'constraint' => 10],
			'nom'                 => ['type' => 'VARCHAR', 'constraint' => 255],
			'prenom'              => ['type' => 'VARCHAR', 'constraint' => 255],
			'profil'              => ['type' => 'TEXT', 'null' => true],
			'groupe'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
			'marqueurDossier'     => ['type' => 'TEXT', 'null' => true],
			'scolarite'           => ['type' => 'TEXT', 'null' => true],
			'diplome'             => ['type' => 'TEXT', 'null' => true],
			'preparation_obtenu'  => ['type' => 'TEXT', 'null' => true],
			'serie'               => ['type' => 'TEXT', 'null' => true],
			'specialitesTerminale'=> ['type' => 'TEXT', 'null' => true],
			'specialiteAbandonne' => ['type' => 'TEXT', 'null' => true],
		//	'noteLycee'           => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
		//	'noteFicheAvenir'     => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
			'noteDossier'         => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
			'commentaire'         => ['type' => 'TEXT', 'null' => true]
		]);
		
		$this->forge->addPrimaryKey('numCandidat');
		$this->forge->createTable('Candidat', true);
	}

	public function down()
	{
		$this->forge->dropTable('Candidat');
	}
}