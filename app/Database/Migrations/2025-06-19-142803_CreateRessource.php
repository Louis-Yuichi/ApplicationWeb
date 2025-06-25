<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRessource extends Migration
{
	public function up()
	{
		$this->forge->addField
		([
			'idEtudiant'       => ['type' => 'INT'    ,                        'null' => false],
			'numeroSemestre'   => ['type' => 'INT'    ,                        'null' => false],
			'codeRessource'    => ['type' => 'VARCHAR', 'constraint' =>     7, 'null' => false],
			'moyenneRessource' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false]
		]);

		$this->forge->addPrimaryKey(['idEtudiant', 'numeroSemestre', 'codeRessource']);

		$this->forge->addForeignKey(['idEtudiant', 'numeroSemestre'], 'Semestre', ['idEtudiant', 'numeroSemestre'], 'CASCADE', 'CASCADE');

		$this->forge->createTable('Ressource', true);
	}

	public function down()
	{
		$this->forge->dropTable('Ressource');
	}
}