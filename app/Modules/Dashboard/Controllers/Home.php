<?php
namespace Modules\Dashboard\Controllers;
use App\Controllers\BaseController;

class Home extends BaseController
{
    public function index()
    {
        // Controller ini hanya bertugas menampilkan halaman dashboard
        return view('Modules\Dashboard\Views\index');
    }

    /**
     * Fungsi untuk mereset (truncate/update) semua data transaksional.
     * HANYA GUNAKAN UNTUK DEVELOPMENT/TESTING.
     */
    public function reset_data()
    {
        // Pastikan fungsi ini tidak dijalankan di environment production
        if (ENVIRONMENT === 'production') {
            return redirect()->to('/')->with('error', 'Fungsi reset tidak diizinkan di environment production.');
        }

        $db = \Config\Database::connect();
        
        // Nonaktifkan foreign key checks untuk sementara
        $db->simpleQuery('SET FOREIGN_KEY_CHECKS = 0');

        // Daftar tabel yang akan di-TRUNCATE (dihapus semua isinya)
        $tablesToTruncate = [
            // Invoice
            'inv_invoice_item',
            'inv_invoice',
            // Job Service
            'opr_service_item',
            'opr_service',
            // Material Request
            'wr_matrequest_item',
            'wr_matrequest',
            // Tambahkan tabel payment jika sudah ada
            'fin_payment',
        ];

        foreach ($tablesToTruncate as $table) {
            if ($db->tableExists($table)) {
                $db->table($table)->truncate();
            }
        }
        
        // --- PERUBAHAN LOGIKA UNTUK opr_schedule ---
        // Update semua status di opr_schedule menjadi 'Pending'
        if ($db->tableExists('opr_schedule')) {
            $db->table('opr_schedule')->update(['opr_schedule_status' => 'Pending']);
        }
        
        // Setelah selesai, aktifkan kembali foreign key checks
        $db->simpleQuery('SET FOREIGN_KEY_CHECKS = 1');

        // Redirect kembali ke dashboard dengan pesan sukses
        return redirect()->to('/')->with('success', 'Semua data transaksional berhasil direset. Status semua jadwal dikembalikan ke Pending.');
    }
}