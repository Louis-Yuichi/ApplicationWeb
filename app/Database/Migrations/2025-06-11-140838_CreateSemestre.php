<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSemestre extends Migration
{
	public function up()
	{
		$this->forge->addField
		([
			'numeroSemestre' => ['type' => 'VARCHAR', 'constraint' => 2, 'null' => false],
			'nbAbsences'     => ['type' => 'INT', 'constraint' => 3, 'null' => false],
			'nbAbsencesJstf' => ['type' => 'INT', 'constraint' => 3, 'null' => false]
		]);

		$this->forge->addPrimaryKey('numeroSemestre');
		$this->forge->createTable('Semestre', true);
	}

	public function down()
	{
		$this->forge->dropTable('Semestre');
	}
}