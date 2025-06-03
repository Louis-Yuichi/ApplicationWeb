<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUtilisateur extends Migration
{
	public function up()
	{
		$this->forge->addField
		([
			'idUtilisateur'     => ['type' => 'INT'    , 'constraint' => 11, 'null' => false, 'unsigned' => true, 'auto_increment' => true],
			'nomUtilisateur'    => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
			'prenomUtilisateur' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
			'mailUtilisateur'   => ['type' => 'VARCHAR', 'constraint' => 78, 'null' => false],
			'mdpUtilisateur'    => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false]
		]);

		$this->forge->addPrimaryKey('idUtilisateur');
		$this->forge->createTable('Utilisateur', true);
	}

	public function down()
	{
		$this->forge->dropTable('Utilisateur');
	}
}