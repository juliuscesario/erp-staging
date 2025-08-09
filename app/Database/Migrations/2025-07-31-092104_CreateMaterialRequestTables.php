<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMaterialRequestTables extends Migration
{
    public function up()
    {
        // Query untuk membuat tabel wr_matrequest
        $this->db->query("CREATE TABLE IF NOT EXISTS `wr_matrequest` (
            `wr_matrequest_uuid` varchar(600) NOT NULL PRIMARY KEY,
            `wr_matrequest_id` BIGINT unsigned NOT NULL,
            `wr_matrequest_contract_uuid` varchar(600) NULL,
            `wr_matrequest_opr_schedule_uuid` varchar(600) NULL,
            `wr_matrequest_no` varchar(600) NULL,
            `wr_matrequest_date` date NULL,
            `wr_matrequest_kar_uuid_sign1` varchar(600) NULL,
            `wr_matrequest_sign1` longtext NULL,
            `wr_matrequest_kar_uuid_sign2` varchar(600) NULL,
            `wr_matrequest_sign2` longtext NULL,
            `wr_matrequest_status` enum('Pending','Done') NULL DEFAULT 'Pending',
            `wr_matrequest_created_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `wr_matrequest_created_by` varchar(600) NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;");

        // Query untuk membuat tabel wr_matrequest_item
        $this->db->query("CREATE TABLE IF NOT EXISTS `wr_matrequest_item` (
            `wr_matrequest_item_matrequest_uuid` varchar(600) NOT NULL,
            `wr_matrequest_item_id` BIGINT unsigned NOT NULL,
            `wr_matrequest_item_inventory_uuid` varchar(600) NULL,
            `wr_matrequest_item_qrcode_link` longtext NULL,
            `wr_matrequest_item_qrcode_image` longtext NULL,
            `wr_matrequest_item_item_qty` BIGINT unsigned NULL,
            `wr_matrequest_item_created_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `wr_matrequest_item_created_by` varchar(600) NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;");
    }

    public function down()
    {
        // Perintah untuk menghapus tabel jika migrasi di-rollback
        $this->forge->dropTable('wr_matrequest');
        $this->forge->dropTable('wr_matrequest_item');
    }
}