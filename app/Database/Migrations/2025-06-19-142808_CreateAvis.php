<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAvis extends Migration
{
	public function up()
	{
		$this->forge->addField
		([
			'idEtudiant'    => ['type' => 'INT'    ,                           'null' => false],
			'idAvis'        => ['type' => 'INT'    , 'auto_increment' => true, 'null' => false],
			'typePoursuite' => ['type' => 'VARCHAR', 'constraint'     =>   20, 'null' => false],
			'typeAvis'      => ['type' => 'VARCHAR', 'constraint'     =>   20, 'null' => false],
			'nbAvis'        => ['type' => 'INT'    ,                           'null' => false],
			'commentaire'   => ['type' => 'TEXT'   ,                           'null' => true]
		]);

		$this->forge->addPrimaryKey('idAvis');

		$this->forge->addForeignKey('idEtudiant', 'Etudiant', 'idEtudiant', 'CASCADE', 'CASCADE');

		$this->forge->createTable('Avis', true);
	}

	public function down()
	{
		$this->forge->dropTable('Avis');
	}
}