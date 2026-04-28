<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocumentTypesTable extends Migration
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
            'type_name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'recipient_type' => [
                'type' => 'ENUM',
                'constraint' => ['bor', 'uac', 'uadmin'],
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'default' => null,
                'useCurrent' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('document_types');
    }

    public function down()
    {
        $this->forge->dropTable('document_types');
    }
}
