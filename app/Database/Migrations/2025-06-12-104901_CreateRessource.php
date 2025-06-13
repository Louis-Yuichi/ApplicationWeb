<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRessource extends Migration
{
	public function up()
	{
		$this->forge->addField
		([
			'idEtudiant'       => ['type' => 'VARCHAR', 'constraint' =>     8, 'null' => false],
			'numeroSemestre'   => ['type' => 'INT'    ,                        'null' => false],
			'codeCompetence'   => ['type' => 'VARCHAR', 'constraint' =>     5, 'null' => false],
			'codeRessource'    => ['type' => 'VARCHAR', 'constraint' =>     7, 'null' => false],
			'nomRessource'     => ['type' => 'VARCHAR', 'constraint' =>    50, 'null' => false],
			'moyenneRessource' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false]
		]);

		$this->forge->addPrimaryKey(['idEtudiant', 'numeroSemestre', 'codeCompetence' , 'codeRessource']);

		$this->forge->addForeignKey(['idEtudiant', 'numeroSemestre', 'codeCompetence'], 'Competence', ['idEtudiant', 'numeroSemestre', 'codeCompetence'], 'CASCADE', 'CASCADE');

		$this->forge->createTable('Ressource', true);
	}

	public function down()
	{
		$this->forge->dropTable('Ressource');
	}
}