<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTestUsers extends Migration
{
    public function up()
    {
        $this->db->table('users')->insertBatch([
            ['username' => 'bor01', 'full_name' => 'Dr. Juan Dela Cruz', 'email' => 'juan.delacruz@university.edu', 'password' => '', 'user_type' => 'bor', 'position' => 'Chairperson', 'is_active' => 1],
            ['username' => 'bor02', 'full_name' => 'Dr. Maria Santos', 'email' => 'maria.santos@university.edu', 'password' => '', 'user_type' => 'bor', 'position' => 'Vice Chairperson', 'is_active' => 1],
            ['username' => 'uac01', 'full_name' => 'Dr. Antonio Rivera', 'email' => 'antonio@university.edu', 'password' => '', 'user_type' => 'uac', 'position' => 'Dean', 'is_active' => 1],
            ['username' => 'uac02', 'full_name' => 'Prof. Ana Garcia', 'email' => 'ana@university.edu', 'password' => '', 'user_type' => 'uac', 'position' => 'Faculty', 'is_active' => 1],
            ['username' => 'uadmin01', 'full_name' => 'Dr. Pedro Lopez', 'email' => 'pedro@university.edu', 'password' => '', 'user_type' => 'uadmin', 'position' => 'Director', 'is_active' => 1],
            ['username' => 'uadmin02', 'full_name' => 'Ms. Rosa Fernandez', 'email' => 'rosa@university.edu', 'password' => '', 'user_type' => 'uadmin', 'position' => 'Manager', 'is_active' => 1],
        ]);
    }

    public function down()
    {
        // Remove test users
        $this->db->table('users')->whereIn('username', ['bor01', 'bor02', 'uac01', 'uac02', 'uadmin01', 'uadmin02'])->delete();
    }
}
