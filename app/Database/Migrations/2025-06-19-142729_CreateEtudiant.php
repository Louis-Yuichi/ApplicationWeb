<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEtudiant extends Migration
{
	public function up()
	{
		$this->forge->addField
		([
			'idEtudiant'       => ['type' => 'INT'     ,                         'null' => false],
			'nomEtudiant'      => ['type' => 'VARCHAR' , 'constraint' =>     50, 'null' => false],
			'prenomEtudiant'   => ['type' => 'VARCHAR' , 'constraint' =>     50, 'null' => false],
			'apprentissage'    => ['type' => 'BOOLEAN' ,                         'null' => false, 'default' => false],
			'parcoursEtudes'   => ['type' => 'VARCHAR' , 'constraint' =>     30, 'null' => false],
			'parcoursBUT'      => ['type' => 'VARCHAR' , 'constraint' =>     80, 'null' => false,
								   'default' => 'A « Réalisation d\'applications : conception, développement, validation »'],
			'mobiliteEtranger' => ['type' => 'VARCHAR' , 'constraint' =>     80, 'null' => false, 'default' => 'Non'],
			'anneePromotion'   => ['type' => 'INT'     ,                         'null' => false]
		]);

		$this->forge->addPrimaryKey('idEtudiant');

		$this->forge->createTable('Etudiant', true);
	}

	public function down()
	{
		$this->forge->dropTable('Etudiant');
	}
}