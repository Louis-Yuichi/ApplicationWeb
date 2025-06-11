<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCandidat extends Migration
{
	public function up()
	{
		$this->forge->addField([
			'numCandidat'           => ['type' => 'VARCHAR', 'constraint' => 20],
			'anneeUniversitaire'    => ['type' => 'VARCHAR', 'constraint' => 10],
			'nom'                   => ['type' => 'VARCHAR', 'constraint' => 255],
			'prenom'                => ['type' => 'VARCHAR', 'constraint' => 255],
			'civilite'              => ['type' => 'VARCHAR', 'constraint' => 10,  'null' => true],
			'profil'                => ['type' => 'TEXT',    'null' => true],
			'boursier'              => ['type' => 'VARCHAR', 'constraint' => 5,   'null' => true],
			'marqueurDossier'       => ['type' => 'TEXT',    'null' => true],
			'scolarite'             => ['type' => 'TEXT',    'null' => true],
			'formation'             => ['type' => 'TEXT',    'null' => true],
			'diplome'               => ['type' => 'TEXT',    'null' => true],
			'typeDiplomeCode'       => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true],
			'preparation_obtenu'    => ['type' => 'TEXT',    'null' => true],
			'serie'                 => ['type' => 'TEXT',    'null' => true],
			'serieCode'             => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true],
			'specialitesTerminale'  => ['type' => 'TEXT',    'null' => true],
			'specialiteAbandonne'   => ['type' => 'TEXT',    'null' => true],
			'specialiteMention'     => ['type' => 'TEXT',    'null' => true],
			'noteDossier'           => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
			'noteGlobale'           => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
			'commentaire'           => ['type' => 'TEXT',    'null' => true]
		]);
		
		$this->forge->addPrimaryKey('numCandidat');
		$this->forge->createTable('Candidat', true);
	}

	public function down()
	{
		$this->forge->dropTable('Candidat');
	}
}