<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSummaryActionsTable extends Migration
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
            'document_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'full_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'position' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'action' => [
                'type' => 'ENUM',
                'constraint' => ['approve', 'disapprove'],
            ],
            'remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'date_signed' => [
                'type' => 'TIMESTAMP',
                'default' => null,
                'useCurrent' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'default' => null,
                'useCurrent' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('document_id', 'documents', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('summary_actions');
    }

    public function down()
    {
        $this->forge->dropTable('summary_actions');
    }
}
