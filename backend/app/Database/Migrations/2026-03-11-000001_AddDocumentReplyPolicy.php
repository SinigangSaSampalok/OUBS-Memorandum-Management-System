<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDocumentReplyPolicy extends Migration
{
    public function up()
    {
        $this->forge->addColumn('documents', [
            'allow_replies' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'after' => 'recipient_type',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('documents', ['allow_replies']);
    }
}
