<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSemestre extends Migration
{
	public function up()
	{
		$this->forge->addField
		([
			'idEtudiant'       => ['type' => 'INT'    , 'null' => false],
			'numeroSemestre'   => ['type' => 'INT'    , 'null' => false],
			'nbAbsencesInjust' => ['type' => 'INT'    , 'null' => false],
			'apprentissage'    => ['type' => 'BOOLEAN', 'null' => false, 'default' => false]
		]);

		$this->forge->addPrimaryKey(['idEtudiant', 'numeroSemestre']);

		$this->forge->addForeignKey('idEtudiant' , 'Etudiant', 'idEtudiant', 'CASCADE', 'CASCADE');

		$this->forge->createTable('Semestre', true);
	}

	public function down()
	{
		$this->forge->dropTable('Semestre');
	}
}