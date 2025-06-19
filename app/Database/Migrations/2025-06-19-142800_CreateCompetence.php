<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompetence extends Migration
{
	public function up()
	{
		$this->forge->addField
		([
			'idEtudiant'        => ['type' => 'INT'    ,                        'null' => false],
			'numeroSemestre'    => ['type' => 'INT'    ,                        'null' => false],
			'codeCompetence'    => ['type' => 'VARCHAR', 'constraint' =>     5, 'null' => false],
			'moyenneCompetence' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false],
			'bonusCompetence'   => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true ],
			'rangCompetence'    => ['type' => 'INT'    ,                        'null' => false]
		]);

		$this->forge->addPrimaryKey(['idEtudiant', 'numeroSemestre' , 'codeCompetence']);

		$this->forge->addForeignKey(['idEtudiant', 'numeroSemestre'], 'Semestre', ['idEtudiant', 'numeroSemestre'], 'CASCADE', 'CASCADE');

		$this->forge->createTable('Competence', true);
	}

	public function down()
	{
		$this->forge->dropTable('Competence');
	}
}