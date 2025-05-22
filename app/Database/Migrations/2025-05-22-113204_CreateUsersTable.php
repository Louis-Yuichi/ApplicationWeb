<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'       => ['type' => 'SERIAL', 'unsigned' => true, 'auto_increment' => true],
            'prenom'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'nom'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'email'    => ['type' => 'VARCHAR', 'constraint' => 150],
            'password' => ['type' => 'VARCHAR', 'constraint' => 255],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('UserApp');
    }

    public function down()
    {
        $this->forge->dropTable('UserApp');
    }
}
