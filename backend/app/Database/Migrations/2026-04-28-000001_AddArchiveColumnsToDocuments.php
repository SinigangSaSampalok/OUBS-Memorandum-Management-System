<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddArchiveColumnsToDocuments extends Migration
{
    public function up()
    {
        // Add archived columns to documents table
        $this->forge->addColumn('documents', [
            'archived' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
            'archived_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'archived_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('documents', 'archived');
        $this->forge->dropColumn('documents', 'archived_at');
        $this->forge->dropColumn('documents', 'archived_by');
    }
}