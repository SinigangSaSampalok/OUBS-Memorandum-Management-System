<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDocumentAvailabilityWindows extends Migration
{
    public function up()
    {
        $this->forge->addColumn('documents', [
            'reply_available_days' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'recipient_type',
            ],
            'download_available_days' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'reply_available_days',
            ],
            'reply_deadline_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'download_available_days',
            ],
            'download_deadline_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'reply_deadline_at',
            ],
        ]);

        $this->forge->modifyColumn('documents', [
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected', 'partially_approved', 'completed', 'closed'],
                'default' => 'pending',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('documents', [
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected', 'partially_approved'],
                'default' => 'pending',
            ],
        ]);

        $this->forge->dropColumn('documents', [
            'reply_available_days',
            'download_available_days',
            'reply_deadline_at',
            'download_deadline_at',
        ]);
    }
}
