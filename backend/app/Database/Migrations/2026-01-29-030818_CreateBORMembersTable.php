<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBORMembersTable extends Migration
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
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'unique' => true,
            ],
            'member_number' => [
                'type' => 'INT',
                'constraint' => 2,
                'comment' => 'Position number 1-12',
            ],
            'committee_role' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'default' => null,
                'useCurrent' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bor_members');
    }

    public function down()
    {
        $this->forge->dropTable('bor_members');
    }
}
