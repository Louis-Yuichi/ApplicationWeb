<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRessource extends Migration
{
	public function up()
	{
		$this->forge->addField
		([
			'numeroRessource'  => ['type' => 'VARCHAR', 'constraint' =>  7, 'null' => false],
			'nomRessource'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
			'moyenneRessource' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false]
		]);

		$this->forge->addPrimaryKey('numeroRessource');
		$this->forge->createTable('Ressource', true);
	}

	public function down()
	{
		$this->forge->dropTable('Ressource');
	}
}