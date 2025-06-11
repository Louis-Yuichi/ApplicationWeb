<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompetence extends Migration
{
	public function up()
	{
		$this->forge->addField
		([
			'numeroCompetence'  => ['type' => 'VARCHAR', 'constraint' =>  5, 'null' => false],
			'nomCompetence'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
			'moyenneCompetence' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false],
			'bonus'             => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false],
			'rang'              => ['type' => 'INT'    , 'constraint' =>  3, 'null' => false]
		]);

		$this->forge->addPrimaryKey('numeroCompetence');
		$this->forge->createTable('Competence', true);
	}

	public function down()
	{
		$this->forge->dropTable('Competence');
	}
}