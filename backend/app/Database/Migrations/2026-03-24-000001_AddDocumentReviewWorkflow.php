<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDocumentReviewWorkflow extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'is_document_reviewer' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'is_active',
            ],
        ]);

        $this->forge->addColumn('documents', [
            // Default to allowed so existing documents remain accessible after enabling this feature.
            // New uploads explicitly set this to "pending".
            'review_status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'allowed', 'not_allowed'],
                'default' => 'allowed',
                'after' => 'status',
            ],
            'reviewed_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'review_status',
            ],
            'reviewed_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'reviewed_by',
            ],
            'review_note' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'reviewed_at',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('documents', ['review_note', 'reviewed_at', 'reviewed_by', 'review_status']);
        $this->forge->dropColumn('users', ['is_document_reviewer']);
    }
}

