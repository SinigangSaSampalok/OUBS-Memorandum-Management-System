<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExpandSignatureImageColumns extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `reply_slips` MODIFY `signature_image` MEDIUMTEXT NULL");
        $this->db->query("ALTER TABLE `users` MODIFY `signature_image` MEDIUMTEXT NULL");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `reply_slips` MODIFY `signature_image` TEXT NULL");
        $this->db->query("ALTER TABLE `users` MODIFY `signature_image` TEXT NULL");
    }
}
