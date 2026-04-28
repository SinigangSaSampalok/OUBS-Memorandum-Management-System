<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCollegeCampusIdToUsersTable extends Migration
{
    public function up()
    {
        $fields = [
            'college_campus_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'position',
            ],
        ];

        $this->forge->addColumn('users', $fields);
        $this->forge->addKey('college_campus_id');
        $this->forge->addForeignKey('college_campus_id', 'college_campuses', 'id', 'SET NULL', 'CASCADE');
        $this->forge->processIndexes('users');
    }

    public function down()
    {
        $this->forge->dropForeignKey('users', 'users_college_campus_id_foreign');
        $this->forge->dropColumn('users', 'college_campus_id');
    }
}
