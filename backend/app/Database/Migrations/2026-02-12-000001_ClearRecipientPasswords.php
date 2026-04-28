<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ClearRecipientPasswords extends Migration
{
    public function up()
    {
        $this->db->table('users')
            ->whereIn('user_type', ['bor', 'uac', 'uadmin'])
            ->set('password', '')
            ->update();
    }

    public function down()
    {
        // Cannot safely restore previous password hashes.
    }
}
