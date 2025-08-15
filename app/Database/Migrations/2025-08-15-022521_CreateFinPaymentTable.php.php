<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinPaymentTable extends Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE `fin_payment` (
            `payment_uuid` varchar(600) NOT NULL PRIMARY KEY,
            `payment_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `payment_no` varchar(600) NULL,
            `payment_date` date NULL,
            `payment_invoice_uuid` varchar(600) NOT NULL,
            `payment_method` varchar(255) NULL,
            `payment_amount` decimal(20,2) NULL,
            `payment_reference` text NULL,
            `payment_created_by` varchar(600) NULL,
            `payment_created_date` datetime DEFAULT CURRENT_TIMESTAMP
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;");
    }

    public function down()
    {
        $this->forge->dropTable('fin_payment');
    }
}