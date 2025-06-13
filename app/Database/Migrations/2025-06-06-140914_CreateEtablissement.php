<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEtablissement extends Migration
{
	public function up()
	{
		// Table Etablissement
		$this->forge->addField
		([
			'idEtablissement'            => ['type' => 'INT'    , 'unsigned'   => true, 'auto_increment' => true],
			'nomEtablissement'           => ['type' => 'VARCHAR', 'constraint' => 255],
			'villeEtablissement'         => ['type' => 'VARCHAR', 'constraint' => 255],
			'codePostalEtablissement'    => ['type' => 'VARCHAR', 'constraint' =>   5],
			'departementEtablissement'   => ['type' => 'VARCHAR', 'constraint' => 255],
			'paysEtablissement'          => ['type' => 'VARCHAR', 'constraint' => 255]
		]);
		
		$this->forge->addPrimaryKey('idEtablissement');
		$this->forge->createTable('Etablissement', true);

		// Table de relation EtudierDans
		$this->forge->addField
		([
			'numCandidat'         => ['type' => 'VARCHAR', 'constraint' => 20],
			'anneeUniversitaire'  => ['type' => 'VARCHAR', 'constraint' => 10], // Ajouter l'année
			'idEtablissement'     => ['type' => 'INT'    , 'unsigned'   => true],
			'noteLycee'           => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
			'noteFicheAvenir'     => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true]
		]);

		$this->forge->addKey(['numCandidat', 'anneeUniversitaire', 'idEtablissement'], true);
		$this->forge->addForeignKey(['numCandidat', 'anneeUniversitaire'], 'Candidat', ['numCandidat', 'anneeUniversitaire'], 'CASCADE', 'CASCADE');
		$this->forge->addForeignKey('idEtablissement', 'Etablissement', 'idEtablissement', 'CASCADE', 'CASCADE');
		
		$this->forge->createTable('EtudierDans', true);
	}

	public function down()
	{
		$this->forge->dropTable('EtudierDans', true);
		$this->forge->dropTable('Etablissement', true);
	}
}