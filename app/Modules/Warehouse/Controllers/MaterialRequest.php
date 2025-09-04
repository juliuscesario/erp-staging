<?php
namespace Modules\Warehouse\Controllers;
use App\Controllers\BaseController;
use Ramsey\Uuid\Uuid;
// Tambahkan/Lengkapi use statement untuk QR Code
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode;
// Call Models for Save
use Modules\Warehouse\Models\MatrequestModel;
use Modules\Warehouse\Models\MatrequestItemModel;
// Call PDF 
use Dompdf\Dompdf;
use Dompdf\Options;

class MaterialRequest extends BaseController
{
    //INDEX
    public function create($contractUUID)
    {
        $db = \Config\Database::connect();
       
        $builder = $db->table('opr_schedule as a');
        $builder->select('a.opr_schedule_uuid, a.opr_schedule_id, a.opr_schedule_date'); // Ambil kolom yang diperlukan 
        $builder->join('mkt_contract as b', 'a.opr_schedule_contract_uuid = b.mkt_contract_uuid', 'left');
        $builder->where('b.mkt_contract_uuid', $contractUUID);
        $builder->orderBy('a.opr_schedule_date', 'DESC'); // Urutkan berdasarkan tanggal terbaru
        $schedules = $builder->get()->getResultArray();

        // 3. Siapkan data untuk dikirim ke view
        $data['schedules'] = $schedules;

        // TAMBAHAN: Ambil data karyawan
        $karyawanBuilder = $db->table('m_karyawan');
        $karyawanBuilder->select('kar_uuid, kar_name');
        $karyawanBuilder->where('kar_aktif', 'Ya');
        $data['karyawan'] = $karyawanBuilder->get()->getResultArray();

        // 4. Kirim data ke view saat memuatnya
        return view('Modules\Warehouse\Views\create', $data);
    }

    //INDEX LIST
    public function index()
    {
        $db = \Config\Database::connect();
        
        // Query untuk mengambil data MR dan menggabungkannya dengan nomor kontrak
        $builder = $db->table('wr_matrequest as a');
        $builder->select('a.wr_matrequest_uuid, a.wr_matrequest_no, a.wr_matrequest_date, a.wr_matrequest_status, b.mkt_contract_no, b.mkt_contract_uuid');
        $builder->join('mkt_contract as b', 'a.wr_matrequest_contract_uuid = b.mkt_contract_uuid', 'left');
        $builder->orderBy('a.wr_matrequest_id', 'DESC'); // Tampilkan yang terbaru di atas

        $data['material_requests'] = $builder->get()->getResultArray();
        print_r($data);exit;
        // Kita akan membuat view baru bernama 'list.php'
        return view('Modules\Warehouse\Views\list', $data);
    }

    //INDEX DETAIL
    public function detail($uuid)
    {
        $db = \Config\Database::connect();

        // Query 1: Ambil data utama MR dan join ke tabel lain
        $main_builder = $db->table('wr_matrequest as a');
        $main_builder->select('a.*, b.mkt_contract_no, c.opr_schedule_date, k1.kar_name as nama_pemberi, k2.kar_name as nama_penerima');
        $main_builder->join('mkt_contract as b', 'a.wr_matrequest_contract_uuid = b.mkt_contract_uuid', 'left');
        $main_builder->join('opr_schedule as c', 'a.wr_matrequest_opr_schedule_uuid = c.opr_schedule_uuid', 'left');
        // Join ke tabel karyawan untuk penanda tangan pertama
        $main_builder->join('m_karyawan as k1', 'a.wr_matrequest_kar_uuid_sign1 = k1.kar_uuid', 'left');
        // Join ke tabel karyawan untuk penanda tangan kedua
        $main_builder->join('m_karyawan as k2', 'a.wr_matrequest_kar_uuid_sign2 = k2.kar_uuid', 'left');
        $main_builder->where('a.wr_matrequest_uuid', $uuid);
        $data['mr'] = $main_builder->get()->getRow();

        // Jika data tidak ditemukan, tampilkan 404
        if (!$data['mr']) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Material Request tidak ditemukan');
        }

        // Query 2: Ambil semua item yang terkait dengan MR ini
        $item_builder = $db->table('wr_matrequest_item as a');
        $item_builder->select('a.*, b.inventory_kode, b.inventory_name');
        $item_builder->join('m_inventory as b', 'a.wr_matrequest_item_inventory_uuid = b.inventory_uuid', 'left');
        $item_builder->where('a.wr_matrequest_item_matrequest_uuid', $uuid);
        $data['items'] = $item_builder->get()->getResult();

        // Kita akan membuat view baru bernama 'detail.php'
        return view('Modules\Warehouse\Views\detail', $data);
    }
    
    //GENERATE ITEMS
    public function generate_items()
    {
        // Langkah 1: Ambil UUID Jadwal & inisialisasi database
        $schedule_uuid = $this->request->getPost('schedule_uuid');
        $db = \Config\Database::connect();

        // Langkah 2: Ambil detail jadwal untuk mendapatkan `period_run`
        $schedule = $db->table('opr_schedule')->where('opr_schedule_uuid', $schedule_uuid)->get()->getRow();
        if (!$schedule) {
            return $this->response->setJSON([]); // Keluar jika jadwal tidak ditemukan
        }
        $period_run = $schedule->opr_schedule_period_run;

        // Langkah 3: Ambil semua item mentah yang terkait dengan jadwal ini
        $item_builder = $db->table('opr_schedule as a');
        $item_builder->select([
            'd.mkt_quotation_order_unit_inventory_uuid',
            'd.mkt_quotation_order_oil_inventory_uuid',
            'd.mkt_quotation_order_item_qty',
            'b.mkt_contract_uuid'
        ]);
        $item_builder->join('mkt_contract as b', 'a.opr_schedule_contract_uuid = b.mkt_contract_uuid');
        $item_builder->join('mkt_quotation as c', 'b.mkt_contract_quotation_uuid = c.mkt_quotation_uuid');
        $item_builder->join('mkt_quotation_order as d', 'c.mkt_quotation_uuid = d.mkt_quotation_order_quotation_uuid');
        $item_builder->where('a.opr_schedule_uuid', $schedule_uuid);
        $quotation_items = $item_builder->get()->getResultArray();

        if (empty($quotation_items)) {
            return $this->response->setJSON([]); // Keluar jika tidak ada item terkait
        }

        // Langkah 4: Proses dan Gabungkan (Grouping) item
        $grouped_items = [];
        foreach ($quotation_items as $item) {
            $inventory_uuids = [
                $item['mkt_quotation_order_unit_inventory_uuid'],
                $item['mkt_quotation_order_oil_inventory_uuid']
            ];
            foreach ($inventory_uuids as $inv_uuid) {
                if (!empty($inv_uuid)) {
                    $details = $db->table('m_inventory')->where('inventory_uuid', $inv_uuid)->get()->getRowArray();
                    if ($details) {
                        if (!isset($grouped_items[$inv_uuid])) {
                            $grouped_items[$inv_uuid] = ['kode' => $details['inventory_kode'], 'nama' => $details['inventory_name'], 'jenis' => $details['inventory_jenis'], 'qty' => 0];
                        }
                        $grouped_items[$inv_uuid]['qty'] += $item['mkt_quotation_order_item_qty'];
                    }
                }
            }
        }

        // Langkah 5: Filter item berdasarkan `period_run` dan buat array final
        $processed_items = [];
        $new_matrequest_uuid = Uuid::uuid4()->toString();
        foreach ($grouped_items as $uuid => $item_data) {
            $should_include = false;
            if ($period_run == 1) { // Bulan pertama, masukkan semua item
                $should_include = true;
            } elseif ($item_data['jenis'] !== 'Asset') { // Bulan > 1, masukkan hanya yang BUKAN Asset
                $should_include = true;
            }

            if ($should_include) {
                $qr_base64 = null;
                $qr_link = null;
                if ($period_run == 1 && $item_data['jenis'] === 'Asset') {
                    $qr_link = "http://staging-erp.perks.id/qrcode/verify/{$quotation_items[0]['mkt_contract_uuid']}/{$new_matrequest_uuid}";
                   // SINTAKS BARU SESUAI CONTOH KLIEN
                    $qrCode = new QrCode(
                        data: $qr_link,
                        encoding: new Encoding('UTF-8'),
                        errorCorrectionLevel: ErrorCorrectionLevel::High,
                        size: 300, // Ukuran disesuaikan agar tidak terlalu besar di tabel
                        margin: 10,
                        roundBlockSizeMode: RoundBlockSizeMode::Margin,
                        foregroundColor: new Color(0, 0, 0),
                        backgroundColor: new Color(255, 255, 255)
                    );
                    $writer = new PngWriter();
                    $qr_base64 = $writer->write($qrCode)->getDataUri();
                }
                $processed_items[] = [
                    'inventory_uuid' => $uuid,
                    'kode'   => $item_data['kode'],
                    'nama'   => $item_data['nama'],
                    'qty'    => $item_data['qty'],
                    'qrcode' => $qr_base64,       // Gambar Base64
                    'qrcode_link' => $qr_link    // <-- TAMBAHAN BARU: URL Asli
                ];
            }
        }

        // Langkah 6: Urutkan hasil akhir
        usort($processed_items, function ($a, $b) {
            $aHasQr = !is_null($a['qrcode']);
            $bHasQr = !is_null($b['qrcode']);
            if ($aHasQr === $bHasQr) return 0;
            return $aHasQr ? -1 : 1;
        });

        // Langkah 7: Kembalikan sebagai respons JSON
        return $this->response->setJSON($processed_items);
    }

    //CHECK SCHEDULE <UDAH GAK KEPAKE?
    public function check_schedule()
    {
        $schedule_uuid = $this->request->getPost('schedule_uuid');
        
        $db = \Config\Database::connect();
        $builder = $db->table('opr_schedule');
        $builder->select('opr_schedule_period_run');
        $builder->where('opr_schedule_uuid', $schedule_uuid);
        $schedule = $builder->get()->getRow();

        // Kembalikan true HANYA jika period_run adalah 1
        $has_items = ($schedule && $schedule->opr_schedule_period_run == 1);

        return $this->response->setJSON(['has_items' => true]);
    }

    //Store 
    public function store()
    {
        // Ambil data JSON dari POST request
        
        $json = json_decode($this->request->getBody());
        $db = \Config\Database::connect();

        try {

            // 2. Gunakan Database Transaction untuk keamanan data
            $db->transStart();

            $matRequestModel = new MatrequestModel();
            $matRequestItemModel = new MatrequestItemModel();

            // 1. Dapatkan ID terakhir dan tambahkan 1
            $lastId = $matRequestModel->selectMax('wr_matrequest_id', 'last_id')->get()->getRow('last_id');
            $new_id = ($lastId ?? 0) + 1;

            // 2. Buat nomor MR sesuai format: MR-ART/bulan-tahun/nomor urut
            $new_no = sprintf('MR-ART/%s-%s/%04d', date('m'), date('y'), $new_id);
            
            $matRequestUUID = Uuid::uuid4()->toString();
            $contractUUID = $db->table('opr_schedule')
                               ->select('opr_schedule_contract_uuid')
                               ->where('opr_schedule_uuid', $json->schedule_uuid)
                               ->get()->getRow()->opr_schedule_contract_uuid;

            // 3. Siapkan & simpan data utama ke tabel wr_matrequest
            $mainData = [
                'wr_matrequest_uuid' => $matRequestUUID,
                'wr_matrequest_id'   => $new_id, // Placeholder untuk ID unik
                'wr_matrequest_no'   => $new_no, // Contoh penomoran
                'wr_matrequest_date' => date('Y-m-d'),
                'wr_matrequest_opr_schedule_uuid' => $json->schedule_uuid,
                'wr_matrequest_contract_uuid' => $contractUUID,
                'wr_matrequest_kar_uuid_sign1' => $json->kar_uuid_1, // Ambil UUID Karyawan 1
                'wr_matrequest_sign1' => $json->signature_data_1,
                'wr_matrequest_kar_uuid_sign2' => $json->kar_uuid_2, // Ambil UUID Karyawan 1
                'wr_matrequest_sign2' => $json->signature_data_2,
                'wr_matrequest_status' => 'Pending'
                // 'wr_matrequest_created_by' => user_id(), // Ambil dari sesi login
            ];
            $matRequestModel->insert($mainData);

            // 4. Loop & simpan setiap item ke tabel wr_matrequest_item
            $item_id_counter = 1;
            foreach ($json->items as $item) {
               $itemData = [
                    'wr_matrequest_item_matrequest_uuid' => $matRequestUUID,
                    'wr_matrequest_item_id'   => $item_id_counter++,
                    'wr_matrequest_item_inventory_uuid' => $item->inventory_uuid,
                    'wr_matrequest_item_qrcode_link' => $item->qrcode_link,
                    'wr_matrequest_item_qrcode_image' => $item->qrcode,
                    'wr_matrequest_item_item_qty' => $item->qty
                ];
                $matRequestItemModel->insert($itemData);
            }

            // 5. Selesaikan transaksi
            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan data.']);
            } else {
                $db->transCommit();
                return $this->response->setJSON(['status' => 'success']);
            }

        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Fungsi baru untuk mengecek apakah sudah ada Material Request
     * untuk schedule_uuid yang diberikan.
     */
    public function checkExistingRequest()
    {
        $schedule_uuid = $this->request->getPost('schedule_uuid');
        
        $db = \Config\Database::connect();
        $builder = $db->table('wr_matrequest');
        $builder->where('wr_matrequest_opr_schedule_uuid', $schedule_uuid);
        $count = $builder->countAllResults();

        return $this->response->setJSON(['exists' => ($count > 0)]);
    }

    //FUNGSI BUAT PRINT PDF
    public function printPdf($uuid)
    {
        $db = \Config\Database::connect();

        // 1. Ambil data utama dan data item (logika query sama seperti fungsi detail)
        $main_builder = $db->table('wr_matrequest as a');
        $main_builder->select('a.*, b.mkt_contract_no, c.opr_schedule_date, k1.kar_name as nama_pemberi, k2.kar_name as nama_penerima');
        $main_builder->join('mkt_contract as b', 'a.wr_matrequest_contract_uuid = b.mkt_contract_uuid', 'left');
        $main_builder->join('opr_schedule as c', 'a.wr_matrequest_opr_schedule_uuid = c.opr_schedule_uuid', 'left');
        $main_builder->join('m_karyawan as k1', 'a.wr_matrequest_kar_uuid_sign1 = k1.kar_uuid', 'left');
        $main_builder->join('m_karyawan as k2', 'a.wr_matrequest_kar_uuid_sign2 = k2.kar_uuid', 'left');
        $main_builder->where('a.wr_matrequest_uuid', $uuid);
        $data['mr'] = $main_builder->get()->getRow();

        $item_builder = $db->table('wr_matrequest_item as a');
        $item_builder->select('a.*, b.inventory_kode, b.inventory_name');
        $item_builder->join('m_inventory as b', 'a.wr_matrequest_item_inventory_uuid = b.inventory_uuid', 'left');
        $item_builder->where('a.wr_matrequest_item_matrequest_uuid', $uuid);
        $data['items'] = $item_builder->get()->getResult();

        if (!$data['mr']) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Material Request tidak ditemukan');
        }

        // 2. Load view khusus untuk PDF (tanpa tombol/navigasi)
        $html = view('Modules\Warehouse\Views\print_detail', $data);
        
        // 3. Inisialisasi Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true); // Izinkan Dompdf memuat gambar dari URL
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        
        // 4. Atur ukuran kertas dan render
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // 5. Stream (kirim) file PDF ke browser
        $dompdf->stream($data['mr']->wr_matrequest_no . ".pdf", ["Attachment" => 0]);
    }

    //PRINT STICKER
    public function printSticker($request_uuid, $item_id)
    {
        $db = \Config\Database::connect();

        // 1. Ambil data satu item spesifik
        $item_builder = $db->table('wr_matrequest_item as a');
        $item_builder->select('a.wr_matrequest_item_qrcode_image, b.inventory_kode, b.inventory_name');
        $item_builder->join('m_inventory as b', 'a.wr_matrequest_item_inventory_uuid = b.inventory_uuid', 'left');
        $item_builder->where('a.wr_matrequest_item_matrequest_uuid', $request_uuid);
        $item_builder->where('a.wr_matrequest_item_id', $item_id);
        $data['item'] = $item_builder->get()->getRow();

        if (!$data['item'] || empty($data['item']->wr_matrequest_item_qrcode_image)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Item atau QR Code tidak ditemukan');
        }

        // 2. Load view khusus untuk stiker
        $html = view('Modules\Warehouse\Views\print_sticker', $data);
        
        // 3. Inisialisasi Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        
        // 4. Atur ukuran kertas kustom (10cm x 10cm)
        $customPaper = [0, 0, 283, 283];
        $dompdf->setPaper($customPaper);
        
        // 5. Render dan stream PDF
        $dompdf->render();
        $filename = "Stiker-" . $data['item']->inventory_kode . ".pdf";
        $dompdf->stream($filename, ["Attachment" => 0]);
    }

}