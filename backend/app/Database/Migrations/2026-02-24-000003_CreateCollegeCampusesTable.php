<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCollegeCampusesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'unique' => true,
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['college', 'campus'],
            ],
            'is_active' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'default' => null,
                'useCurrent' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'default' => null,
                'useCurrent' => true,
                'onUpdate' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('college_campuses');
    }

    public function down()
    {
        $this->forge->dropTable('college_campuses');
    }
}
