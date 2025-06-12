<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEtudiant extends Migration
{
	public function up()
	{
		$this->forge->addField
		([
			'idEtudiant'     => ['type' => 'VARCHAR' , 'constraint' =>  8, 'null' => false],
			'nomEtudiant'    => ['type' => 'VARCHAR' , 'constraint' => 50, 'null' => false],
			'prenomEtudiant' => ['type' => 'VARCHAR' , 'constraint' => 50, 'null' => false],
			'annee'          => ['type' => 'VARCHAR' , 'constraint' =>  9, 'null' => false]
		]);

		$this->forge->addPrimaryKey('idEtudiant');

		$this->forge->createTable('Etudiant', true);
	}

	public function down()
	{
		$this->forge->dropTable('Etudiant');
	}
}