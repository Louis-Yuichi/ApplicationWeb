<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFiltre extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idFiltre' => [
                'type' => 'SERIAL',
                'unsigned' => true,
            ],
            'nomFiltre' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'typeAction' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'comment' => 'bonus, malus, coefficient, note_directe'
            ],
            'valeurAction' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'colonneSource' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'conditionType' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'comment' => 'contient, egal, different, commence_par, finit_par'
            ],
            'valeurCondition' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'actif' => [
                'type' => 'SMALLINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('idFiltre', true);
        $this->forge->createTable('Filtre');
        
        // Ajouter les contraintes CHECK pour simuler les ENUM
        $this->db->query("ALTER TABLE \"Filtre\" ADD CONSTRAINT check_type_action CHECK (\"typeAction\" IN ('bonus', 'malus', 'coefficient', 'note_directe'))");
        $this->db->query("ALTER TABLE \"Filtre\" ADD CONSTRAINT check_condition_type CHECK (\"conditionType\" IN ('contient', 'egal', 'different', 'commence_par', 'finit_par'))");
    }

    public function down()
    {
        $this->forge->dropTable('Filtre');
    }
}
