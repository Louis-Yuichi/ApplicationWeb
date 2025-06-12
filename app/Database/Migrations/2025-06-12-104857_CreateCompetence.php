<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompetence extends Migration
{
	public function up()
	{
		$this->forge->addField
		([
			'idEtudiant'        => ['type' => 'VARCHAR', 'constraint' =>     8, 'null' => false],
			'numeroCompetence'  => ['type' => 'VARCHAR', 'constraint' =>     5, 'null' => false],
			'numeroSemestre'    => ['type' => 'VARCHAR', 'constraint' =>     2, 'null' => false],
			'nomCompetence'     => ['type' => 'VARCHAR', 'constraint' =>    50, 'null' => false],
			'moyenneCompetence' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false],
			'bonus'             => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false],
			'rang'              => ['type' => 'INT'    , 'null' => false]
		]);

		$this->forge->addPrimaryKey(['idEtudiant'   , 'numeroCompetence', 'numeroSemestre']);

		$this->forge->addForeignKey('idEtudiant'    , 'Etudiant'        , 'idEtudiant'    , 'CASCADE', 'CASCADE');

		$this->forge->createTable('Competence', true);
	}

	public function down()
	{
		$this->forge->dropTable('Competence');
	}
}