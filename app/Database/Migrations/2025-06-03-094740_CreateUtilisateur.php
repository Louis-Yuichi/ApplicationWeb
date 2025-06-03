<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUtilisateur extends Migration
{
	public function up()
	{
		$this->forge->addField
		([
			'idUtilisateur'     => ['type' => 'INT'    , 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
			'nomUtilisateur'    => ['type' => 'VARCHAR', 'constraint' => 50],
			'prenomUtilisateur' => ['type' => 'VARCHAR', 'constraint' => 50],
			'mailUtilisateur'   => ['type' => 'VARCHAR', 'constraint' => 78],
			'mdpUtilisateur'    => ['type' => 'VARCHAR', 'constraint' => 64]
		]);

		$this->forge->addPrimaryKey('idUtilisateur');
		$this->forge->createTable('Utilisateur', true);
	}

	public function down()
	{
		$this->forge->dropTable('Utilisateur');
	}
}