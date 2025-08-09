<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJobServiceTables extends Migration
{
    public function up()
    {
        // Query untuk membuat tabel wr_matrequest
        $this->db->query("CREATE TABLE `opr_service` (
            `opr_service_uuid` varchar(600) NOT NULL PRIMARY KEY,
            `opr_service_id` bigint(20) UNSIGNED NOT NULL,
            `opr_service_no` varchar(600) NULL,
            `opr_service_date` date NULL,
            `opr_service_schedule_uuid` varchar(600) NULL,
            `opr_service_cust_pic_uuid` varchar(600) NULL,
            `opr_service_cust_sign_name` varchar(600) NULL,
            `opr_service_cust_sign_position` varchar(600) NULL,
            `opr_service_cust_pic_sign` longtext NULL, -- Mengganti nama dari _sign_image
            `opr_service_work_duration` varchar(255) NULL, -- Memindahkan dari item ke sini jika durasi dicatat per job
            `opr_service_status` enum('Pending','Done') DEFAULT 'Pending',
            `opr_service_created_by` varchar(600) NULL,
            `opr_service_created_date` datetime DEFAULT CURRENT_TIMESTAMP
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;");

        // Query untuk membuat tabel wr_matrequest_item
        $this->db->query("CREATE TABLE `opr_service_item` (
            `opr_service_item_uuid` varchar(600) NOT NULL PRIMARY KEY,
            `opr_service_item_id` BIGINT unsigned NOT NULL, -- Sesuai dari klien
            `opr_service_item_service_uuid` varchar(600) NULL,
            `opr_service_matrequest_uuid` varchar(600) NULL,   -- <-- KOLOM YANG ANDA MAKSUD
            `opr_service_item_inventory_uuid` varchar(600) NULL,
            `opr_service_item_building_uuid` varchar(600) NULL,
            `opr_service_item_room_uuid` varchar(600) NULL,
            `opr_service_item_problem` text NULL,
            `opr_service_item_action` text NULL,
            `opr_service_item_operation` VARCHAR(255) NULL,
            `opr_service_item_image` VARCHAR(255) NULL,
            `opr_service_item_status` enum('Pending','Done') NULL DEFAULT 'Pending', -- Sesuai dari klien
            `opr_service_item_created_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `opr_service_item_created_by` varchar(600) NULL
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;");
    }

    public function down()
    {
        // Perintah untuk menghapus tabel jika migrasi di-rollback
        $this->forge->dropTable('js_service');
        $this->forge->dropTable('js_service_item');
    }
}
