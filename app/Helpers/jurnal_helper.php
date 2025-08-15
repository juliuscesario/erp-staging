<?php

use Ramsey\Uuid\Uuid;

if (!function_exists('buatJurnalInvoice')) {
    /**
     * Fungsi untuk membuat entri jurnal akuntansi secara otomatis
     * setiap kali invoice baru dibuat.
     *
     * @param string $invoice_uuid UUID dari invoice yang baru dibuat.
     * @return bool True jika berhasil, false jika gagal.
     */
    function buatJurnalInvoice(string $invoice_uuid): bool
    {
        $db = \Config\Database::connect();

        $invoice = $db->table('inv_invoice as a')
            ->select('a.*, c.cust_name')
            ->join('m_cust as c', 'a.inv_invoice_cust_uuid = c.cust_uuid', 'left')
            ->where('a.inv_invoice_uuid', $invoice_uuid)
            ->get()->getRowArray();

        if (!$invoice) {
            log_message('error', 'Invoice ' . $invoice_uuid . ' tidak ditemukan untuk pembuatan jurnal.');
            return false;
        }

        try {
            $db->transStart();

            $tgl_posting = $invoice['inv_invoice_date'];
            $no_trans = $invoice['inv_invoice_no'];
            $keterangan = 'Penjualan Jasa atas nama ' . $invoice['cust_name'];
            $sumber_uuid = $invoice['inv_invoice_uuid'];
            $user_pembuat = 'System'; // Ganti dengan user session jika perlu

            $jurnal_entries = [
                ['trans_no_acc' => '11310101', 'trans_DB' => $invoice['inv_invoice_grand_total'], 'trans_CR' => 0.00, 'trans_ket' => $keterangan],
                ['trans_no_acc' => '41010101', 'trans_DB' => 0.00, 'trans_CR' => $invoice['inv_invoice_subtotal'], 'trans_ket' => 'Pendapatan ' . $keterangan],
                ['trans_no_acc' => '21410101', 'trans_DB' => 0.00, 'trans_CR' => $invoice['inv_invoice_ppn_total'], 'trans_ket' => 'PPN Keluaran atas ' . $keterangan],
                ['trans_no_acc' => '43010101', 'trans_DB' => $invoice['inv_invoice_discount'], 'trans_CR' => 0.00, 'trans_ket' => 'Diskon atas ' . $keterangan]
            ];
            
            $lastTrans = $db->table('data_2025_trans')->selectMax('trans_id', 'last_id')->get()->getRow();
            $next_trans_id = ($lastTrans && $lastTrans->last_id) ? $lastTrans->last_id + 1 : 1;

            foreach ($jurnal_entries as $entry) {
                if ($entry['trans_DB'] == 0 && $entry['trans_CR'] == 0) continue;
                
                $entry['trans_uuid'] = Uuid::uuid4()->toString();
                $entry['trans_id'] = $next_trans_id++;
                $entry['trans_tgl'] = $tgl_posting;
                $entry['trans_no_trans'] = $no_trans;
                $entry['trans_source'] = 'Invoice';
                $entry['trans_source_uuid'] = $sumber_uuid;
                $entry['trans_created_by'] = $user_pembuat;
                
                $db->table('data_2025_trans')->insert($entry);
            }

            $db->transComplete();
            return $db->transStatus();

        } catch (\Exception $e) {
            log_message('error', 'Exception saat membuat jurnal: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('balikJurnalInvoice')) {
    function balikJurnalInvoice(string $invoice_uuid): bool
    {
        $db = \Config\Database::connect();
        $invoice = $db->table('inv_invoice')->getWhere(['inv_invoice_uuid' => $invoice_uuid])->getRowArray();

        if (!$invoice) {
            log_message('error', 'Invoice ' . $invoice_uuid . ' tidak ditemukan untuk jurnal pembalik.');
            return false;
        }

        $jurnalLama = $db->table('data_2025_trans as a')
            ->select('a.*, b.nama_acc') // Ambil nama akun
            ->join('data_2025_sub as b', 'a.trans_no_acc = b.no_acc', 'left')
            ->where('a.trans_source_uuid', $invoice_uuid)
            ->get()->getResultArray();

        if (empty($jurnalLama)) return true;

        try {
            $db->transStart();

            $tgl_posting = date('Y-m-d');
            $no_trans = $invoice['inv_invoice_no'];
            $user_pembuat = 'System';

            $lastTrans = $db->table('data_2025_trans')->selectMax('trans_id', 'last_id')->get()->getRow();
            $next_trans_id = ($lastTrans && $lastTrans->last_id) ? $lastTrans->last_id + 1 : 1;

            foreach ($jurnalLama as $jurnal) {
                // Keterangan dibuat lebih spesifik
                $keterangan = 'Koreksi ' . ($jurnal['nama_acc'] ?? 'Jurnal') . ' untuk Invoice ' . $no_trans;

                $entryPembalik = [
                    'trans_uuid'        => Uuid::uuid4()->toString(),
                    'trans_id'          => $next_trans_id++,
                    'trans_tgl'         => $tgl_posting,
                    'trans_no_trans'    => $no_trans,
                    'trans_ket'         => $keterangan, // Keterangan baru
                    'trans_no_acc'      => $jurnal['trans_no_acc'],
                    'trans_DB'          => $jurnal['trans_CR'], // Dibalik
                    'trans_CR'          => $jurnal['trans_DB'], // Dibalik
                    'trans_source'      => 'Invoice_Adjustment', // Sumber dibuat beda untuk penanda
                    'trans_source_uuid' => $invoice_uuid,
                    'trans_created_by'  => $user_pembuat,
                ];
                $db->table('data_2025_trans')->insert($entryPembalik);
            }
            
            $db->transComplete();
            return $db->transStatus();

        } catch (\Exception $e) {
            log_message('error', 'Exception saat membalik jurnal: ' . $e->getMessage());
            return false;
        }
    }
}


if (!function_exists('catatJurnalPembayaran')) {
    /**
     * Fungsi untuk mencatat JURNAL PEMBAYARAN dengan logika akuntansi yang sudah diperbaiki.
     * Jurnal ini mencatat pelunasan piutang.
     */
    function catatJurnalPembayaran($invoice, $payment)
    {
        $db = \Config\Database::connect();
        
        // Nomor Akun dari data_2025_sub
        $no_acc_piutang = '11310101'; // Piutang Dagang
        $no_acc_kas_bank = ($payment['payment_method'] === 'Cash') ? '11110001' : '11120001'; // Kas atau Bank
        $no_acc_pph23 = '11730103'; // Uang Muka PPh 23

        try {
            $db->transStart();

            $lastTrans = $db->table('data_2025_trans')->selectMax('trans_id', 'last_id')->get()->getRow();
            $next_trans_id = ($lastTrans && $lastTrans->last_id) ? $lastTrans->last_id + 1 : 1;
            
            $keterangan = 'Penerimaan Pembayaran Invoice ' . $invoice->inv_invoice_no . ' a/n ' . $invoice->cust_name;

            if ($payment['payment_type'] === 'Bayar Penuh') {
                // --- JURNAL BAYAR PENUH (Debit: Kas/Bank, Kredit: Piutang) ---
                $db->table('data_2025_trans')->insert([
                    'trans_uuid' => Uuid::uuid4()->toString(), 'trans_id' => $next_trans_id++, 'trans_tgl' => $payment['payment_date'],
                    'trans_no_trans' => $payment['payment_no'], 'trans_ket' => $keterangan, 'trans_no_acc' => $no_acc_kas_bank,
                    'trans_DB' => $invoice->inv_invoice_grand_total, 'trans_CR' => 0.00, 'trans_source' => 'Payment', 'trans_source_uuid' => $payment['payment_uuid'],
                ]);
                $db->table('data_2025_trans')->insert([
                    'trans_uuid' => Uuid::uuid4()->toString(), 'trans_id' => $next_trans_id++, 'trans_tgl' => $payment['payment_date'],
                    'trans_no_trans' => $payment['payment_no'], 'trans_ket' => $keterangan, 'trans_no_acc' => $no_acc_piutang,
                    'trans_DB' => 0.00, 'trans_CR' => $invoice->inv_invoice_grand_total, 'trans_source' => 'Payment', 'trans_source_uuid' => $payment['payment_uuid'],
                ]);
            } else {
                // --- JURNAL BAYAR DENGAN PPH 23 (Jurnal Majemuk) ---
                $dpp = $invoice->inv_invoice_subtotal - $invoice->inv_invoice_discount;
                $pph_amount = $dpp * 0.02;

                // DEBIT: Kas/Bank (uang yang diterima)
                $db->table('data_2025_trans')->insert([
                    'trans_uuid' => Uuid::uuid4()->toString(), 'trans_id' => $next_trans_id++, 'trans_tgl' => $payment['payment_date'],
                    'trans_no_trans' => $payment['payment_no'], 'trans_ket' => $keterangan, 'trans_no_acc' => $no_acc_kas_bank,
                    'trans_DB' => $payment['payment_amount'], 'trans_CR' => 0.00, 'trans_source' => 'Payment', 'trans_source_uuid' => $payment['payment_uuid'],
                ]);

                // DEBIT: Uang Muka PPh 23
                $db->table('data_2025_trans')->insert([
                    'trans_uuid' => Uuid::uuid4()->toString(), 'trans_id' => $next_trans_id++, 'trans_tgl' => $payment['payment_date'],
                    'trans_no_trans' => $payment['payment_no'], 'trans_ket' => 'Potongan PPh 23 ' . $keterangan, 'trans_no_acc' => $no_acc_pph23,
                    'trans_DB' => $pph_amount, 'trans_CR' => 0.00, 'trans_source' => 'Payment', 'trans_source_uuid' => $payment['payment_uuid'],
                ]);

                // KREDIT: Piutang Usaha (sebesar grand total)
                $db->table('data_2025_trans')->insert([
                    'trans_uuid' => Uuid::uuid4()->toString(), 'trans_id' => $next_trans_id++, 'trans_tgl' => $payment['payment_date'],
                    'trans_no_trans' => $payment['payment_no'], 'trans_ket' => $keterangan, 'trans_no_acc' => $no_acc_piutang,
                    'trans_DB' => 0.00, 'trans_CR' => $invoice->inv_invoice_grand_total, 'trans_source' => 'Payment', 'trans_source_uuid' => $payment['payment_uuid'],
                ]);
            }

            $db->transComplete();
            return $db->transStatus();

        } catch (\Exception $e) {
            log_message('error', 'Exception saat mencatat jurnal pembayaran: ' . $e->getMessage());
            return false;
        }
    }
}