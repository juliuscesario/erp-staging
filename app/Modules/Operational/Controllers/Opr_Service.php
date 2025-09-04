<?php
namespace Modules\Operational\Controllers;
use App\Controllers\BaseController;
use Modules\Operational\Models\MOprService;
use Modules\Operational\Models\MOprServiceItem;
// Call PDF 
use Dompdf\Dompdf;
use Dompdf\Options;
// Call UUID
use Ramsey\Uuid\Uuid;

class Opr_Service extends BaseController
{
    protected $db; // Properti untuk menampung koneksi DB

    /**
     * Private helper method untuk mengambil dan memproses semua data
     * yang dibutuhkan oleh halaman detail dan edit.
     */
   // app/Modules/Operational/Controllers/Opr_Service.php

    private function _getServiceDataForView($uuid)
    {
        $serviceModel = new \Modules\Operational\Models\MOprService();
        $data = $serviceModel->getServiceDataSet($uuid, 'service');

        if (empty($data['main'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Job Service tidak ditemukan');
        }

        // --- AWAL PERBAIKAN ---
        // Logika bisnis tambahan untuk menghitung hari dan periode
        if (!empty($data['main']['opr_schedule_date'])) {
            $schedule_date = new \DateTime($data['main']['opr_schedule_date']);
            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $data['main']['service_day'] = $days[$schedule_date->format('w')];
            
            $end_period_date = clone $schedule_date;
            $end_period_date->modify('+1 month -1 day');
            $data['main']['service_period_full'] = $schedule_date->format('d M Y') . ' - ' . $end_period_date->format('d M Y');
        }
        // --- AKHIR PERBAIKAN ---

        return $data; // Kembalikan $data asli yang berisi array
    }
    
    //Construct for using $db
    public function __construct()
    {
        // Inisialisasi koneksi database
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // Fungsi ini sekarang hanya menampilkan kerangka view
        return view('Modules\Operational\Views\list');
    }

    public function load_list()
    {
        $request = \Config\Services::request();
        $serviceModel = new \Modules\Operational\Models\MOprService(); 

        $start = $request->getPost('start');
        $length = $request->getPost('length');
        $searchValue = $request->getPost('search')['value'];
        $order = $request->getPost('order');

        // Panggil fungsi dari model, minta hasil sebagai array
        $list = $serviceModel->getDataTable($start, $length, $searchValue, $order);
        $recordsTotal = $serviceModel->countAllData();
        $recordsFiltered = $serviceModel->countFilteredData($searchValue);

        $data = [
            "draw" => intval($request->getPost('draw')),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $list,
        ];

        return $this->response->setJSON($data);
    }
    
    //CREATE Function
    public function create($contractUUID = null)
    {
        // Jika tidak ada contract UUID yang diberikan, tampilkan pesan error atau halaman kosong
        if (!$contractUUID) {
            // Anda bisa menampilkan view error di sini
            return "Error: Contract UUID tidak disediakan.";
        }

        $db = \Config\Database::connect();
        $data = [];

        // Langkah 1: Cek apakah ada invoice yang overdue untuk kontrak ini
        $overdue_builder = $db->table('inv_invoice');
        $overdue_builder->where('inv_invoice_contract_uuid', $contractUUID);
        $overdue_builder->where('inv_invoice_status', 'Unpaid');
        $overdue_builder->where('inv_invoice_due_date <', date('Y-m-d'));
        $overdue_invoices = $overdue_builder->get()->getResultArray();

        if (!empty($overdue_invoices)) {
            // Jika ADA invoice overdue, kirim sinyal 'on_hold' dan daftarnya
            $data['on_hold'] = true;
            $data['overdue_invoices'] = $overdue_invoices;
            $data['schedules'] = []; // Kirim array kosong untuk jadwal
        } else {
            // Jika TIDAK ADA, lanjutkan untuk mengambil jadwal yang valid
            $data['on_hold'] = false;
            $data['overdue_invoices'] = []; // Kirim array kosong agar tidak error

            // Subquery 1: Memeriksa apakah Material Request untuk periode 1 (instalasi) sudah ada
            $subquery_mr_exists = $db->table('opr_schedule as s2')
                                    ->join('wr_matrequest as mr', 's2.opr_schedule_uuid = mr.wr_matrequest_opr_schedule_uuid')
                                    ->where('s2.opr_schedule_contract_uuid = b.mkt_contract_uuid')
                                    ->where('s2.opr_schedule_period_run', 1)
                                    ->select('1')
                                    ->getCompiledSelect();

            // Subquery 2: Memeriksa apakah ada invoice yang overdue untuk kontrak terkait
            $subquery_overdue_exists = $db->table('inv_invoice as inv')
                                        ->where('inv.inv_invoice_contract_uuid = b.mkt_contract_uuid')
                                        ->where('inv.inv_invoice_status', 'Unpaid')
                                        ->where('inv.inv_invoice_due_date <', date('Y-m-d'))
                                        ->select('1')
                                        ->getCompiledSelect();
            // Query utama untuk mengambil daftar jadwal servis yang "aman" untuk kontrak ini
            $builder = $db->table('opr_schedule as a');
            $builder->select('a.opr_schedule_uuid, a.opr_schedule_id, a.opr_schedule_date, c.cust_name');
            $builder->join('mkt_contract as b', 'a.opr_schedule_contract_uuid = b.mkt_contract_uuid', 'left');
            $builder->join('m_cust as c', 'b.mkt_contract_cust_uuid = c.cust_uuid', 'left');
            
            // Filter utama berdasarkan contractUUID dari URL
            $builder->where('a.opr_schedule_contract_uuid', $contractUUID);
            
            $builder->where('a.opr_schedule_status', 'Pending');
            $builder->where("EXISTS ({$subquery_mr_exists})", null, false);
            $builder->where("NOT EXISTS ({$subquery_overdue_exists})", null, false);
            
            $builder->orderBy('a.opr_schedule_date', 'ASC');
            $data['schedules'] = $builder->get()->getResultArray();
        
        }
        return view('Modules\Operational\Views\index', $data);
    }

    //LOAD SERVICE DATA UNTUK FORM
    public function loadServiceData()
    {
        $schedule_uuid = $this->request->getPost('schedule_uuid');
        $serviceModel = new \Modules\Operational\Models\MOprService();
        
        // Cukup panggil satu fungsi
        $data = $serviceModel->getServiceDataSet($schedule_uuid, 'schedule');

        if ($data['main']) {
            // Logika bisnis tetap di controller
            $schedule_date = new \DateTime($data['main']['opr_schedule_date']);
            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $data['main']['service_day'] = $days[$schedule_date->format('w')];
            $end_period_date = clone $schedule_date;
            $end_period_date->modify('+1 month -1 day');
            $data['main']['service_period_full'] = $schedule_date->format('d M Y') . ' - ' . $end_period_date->format('d M Y');
        }
        
        return $this->response->setJSON($data);
    }

    //VALIDATE SCHEDULE
    /**
     * Fungsi baru untuk validasi cepat via AJAX.
     * Mengecek apakah MR untuk instalasi awal (Periode 1) ada.
     */
    public function validateSchedule()
    {
        $schedule_uuid = $this->request->getPost('schedule_uuid');
        $db = \Config\Database::connect();

        // Cari contract_uuid dari jadwal yang dipilih
        $schedule = $db->table('opr_schedule')->where('opr_schedule_uuid', $schedule_uuid)->get()->getRow();
        if (!$schedule) {
            return $this->response->setJSON(['isValid' => false, 'message' => 'Jadwal tidak ditemukan.']);
        }

        // Cari jadwal instalasi (periode = 1) untuk kontrak yang sama
        $installation_schedule = $db->table('opr_schedule')
                                    ->where('opr_schedule_contract_uuid', $schedule->opr_schedule_contract_uuid)
                                    ->where('opr_schedule_period_run', 1)
                                    ->get()->getRow();
        if (!$installation_schedule) {
            return $this->response->setJSON(['isValid' => false, 'message' => 'Jadwal instalasi (Periode 1) untuk kontrak ini tidak ditemukan.']);
        }

        // Cek apakah ada Material Request yang dibuat berdasarkan jadwal instalasi
        $material_request = $db->table('wr_matrequest')
                               ->where('wr_matrequest_opr_schedule_uuid', $schedule_uuid)
                               ->get()->getRow();
        
        if (!$material_request) {
            return $this->response->setJSON(['isValid' => false, 'message' => 'Material Request untuk periode belum dibuat. Job Service tidak bisa dilanjutkan.']);
        }

        // Jika semua validasi lolos
        return $this->response->setJSON(['isValid' => true]);
    }

    public function detail($uuid)
    {
        $viewData = $this->_getServiceDataForView($uuid);
        // Ganti nama key agar sesuai dengan view
        $viewData['service'] = $viewData['main'];
        return view('Modules\Operational\Views\detail', $viewData);
    }

    public function edit($uuid)
    {
        $viewData = $this->_getServiceDataForView($uuid);
        // Ganti nama key agar sesuai dengan view
        $viewData['service'] = $viewData['main'];
        return view('Modules\Operational\Views\edit', $viewData);
    }

    //PRint function
    public function printService($uuid)
    {
        $db = \Config\Database::connect();
        $data = [];

        // Langkah 1: Ambil data utama Service (sebagai ARRAY)
        $main_builder = $db->table('opr_service as a');
        $main_builder->select('
            a.*, 
            s.opr_schedule_date, s.opr_schedule_period_run, s.opr_schedule_period_total,
            con.mkt_contract_no, con.mkt_contract_quotation_uuid,
            cust.cust_name,
            pic.cust_pic_kontak as cust_pic_name,
            bldg.building_name, bldg.building_add1, bldg.building_kelurahan, bldg.building_kecamatan, bldg.building_kabupaten, bldg.building_kode_pos,
            br.branch_alamat, br.branch_kelurahan, br.branch_kecamatan, br.branch_kabupaten, 
            br.branch_provinsi, br.branch_negara, br.branch_kode_pos, br.branch_phone1
        ');
        $main_builder->join('opr_schedule as s', 'a.opr_service_schedule_uuid = s.opr_schedule_uuid', 'left');
        $main_builder->join('mkt_contract as con', 's.opr_schedule_contract_uuid = con.mkt_contract_uuid', 'left');
        $main_builder->join('m_cust as cust', 'con.mkt_contract_cust_uuid = cust.cust_uuid', 'left');
        $main_builder->join('m_cust_pic as pic', 'a.opr_service_cust_pic_uuid = pic.cust_pic_uuid', 'left');
        $main_builder->join('mkt_quotation as q', 'con.mkt_contract_quotation_uuid = q.mkt_quotation_uuid', 'left');
        $main_builder->join('m_branch as br', 'br.branch_uuid = q.mkt_quotation_branch_uuid','left');
        $main_builder->join('m_building as bldg', 'FIND_IN_SET(bldg.building_uuid, q.mkt_quotation_building_uuid)', 'left');
        $main_builder->where('a.opr_service_uuid', $uuid);
        $main_builder->groupBy('a.opr_service_uuid');
        $service_data = $main_builder->get()->getRowArray(); // Diubah ke getRowArray()

        if (!$service_data) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Job Service tidak ditemukan');
        }

        // Langkah 2: Hitung Hari dan Periode Tanggal
        if (!empty($service_data['opr_schedule_date'])) {
            $schedule_date = new \DateTime($service_data['opr_schedule_date']);
            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $service_data['service_day'] = $days[$schedule_date->format('w')];
            $end_period_date = clone $schedule_date;
            $end_period_date->modify('+1 month -1 day');
            $service_data['service_period_full'] = $schedule_date->format('d M Y') . ' - ' . $end_period_date->format('d M Y');
        }

        // Langkah 3: Ambil semua item checklist
        $item_builder = $db->table('opr_service_item as item');
        $item_builder->select('item.*, inv.inventory_kode, inv.inventory_name, room.room_name, bldg.building_name');
        $item_builder->join('m_inventory as inv', 'item.opr_service_item_inventory_uuid = inv.inventory_uuid', 'left');
        $item_builder->join('m_building as bldg', 'item.opr_service_item_building_uuid = bldg.building_uuid', 'left');
        $item_builder->join('m_room as room', 'item.opr_service_item_room_uuid = room.room_uuid', 'left');
        $item_builder->where('item.opr_service_item_service_uuid', $uuid);
        $items_data = $item_builder->get()->getResultArray(); // Diubah ke getResultArray()

        $data['service'] = $service_data;
        $data['items'] = $items_data;

        // Load view untuk PDF
        $html = view('Modules\Operational\Views\print_service', $data);
        
        // Inisialisasi Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        
        // Atur ukuran kertas dan render
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Buat footer
        $address_parts = [
            $service_data['branch_alamat'] ?? null,
            $service_data['branch_kelurahan'] ?? null,
            isset($service_data['branch_kecamatan']) ? 'Kec. ' . $service_data['branch_kecamatan'] : null,
            $service_data['branch_kabupaten'] ?? null,
            $service_data['branch_provinsi'] ?? null,
        ];
        $full_address = implode(', ', array_filter($address_parts));
        $footerText = $full_address . ' ' . ($service_data['branch_kode_pos'] ?? '') . ', ' . ($service_data['branch_negara'] ?? '') . ' Telp ' . ($service_data['branch_phone1'] ?? '');

        $canvas = $dompdf->get_canvas();
        $canvas->page_text(36, $canvas->get_height() - 36, $footerText, null, 8, [0.5, 0.5, 0.5]);
        
        // Stream file PDF ke browser
        $dompdf->stream($data['service']['opr_service_no'] . ".pdf", ["Attachment" => 0]);
    }

    //Function Update
    public function update($uuid)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $MOprService = new \Modules\Operational\Models\MOprService();
        $MOprServiceItem = new \Modules\Operational\Models\MOprServiceItem();

        // 1. Siapkan data utama untuk di-update di tabel opr_service
        $mainData = [
            'opr_service_cust_pic_uuid' => $this->request->getPost('cust_pic_uuid'),
            'opr_service_cust_sign_name' => $this->request->getPost('cust_sign_name'),
            'opr_service_cust_sign_position' => $this->request->getPost('cust_sign_position'),
        ];

        // Hanya update tanda tangan & status jika ada gambar baru yang di-submit
        if (!empty($this->request->getPost('cust_sign_image'))) {
            $mainData['opr_service_cust_pic_sign'] = $this->request->getPost('cust_sign_image');
            $mainData['opr_service_status'] = 'Done'; // <-- TAMBAHKAN INI
        }
        
        // Lakukan update pada data utama
        $MOprService->update($uuid, $mainData);

        // 2. Loop & update setiap item di tabel opr_service_item
        
        $items = $this->request->getPost('items');
        if (!empty($items)) {
            foreach ($items as $item_uuid => $item_data) {
                $updateData = [
                    'opr_service_item_problem' => $item_data['problem'],
                    'opr_service_item_action' => $item_data['action'],
                    'opr_service_item_operation' => $item_data['work_duration'],
                ];

            // Cek apakah ada data Base64 foto baru yang dikirim
            if (isset($item_data['photo_base64']) && !empty($item_data['photo_base64'])) {
                $updateData['opr_service_item_image'] = $item_data['photo_base64'];
            }
                $MOprServiceItem->update($item_uuid, $updateData);
            }
        
        }

        $db->transComplete();
        
        if ($db->transStatus() === false) {
            // Jika transaksi gagal, kembali dengan pesan error
            return redirect()->back()->with('error', 'Gagal memperbarui Job Service.');
        }

        // 3. Redirect kembali ke halaman detail setelah update berhasil
        return redirect()->to(site_url('operational/opr_service/detail/' . $uuid))->with('success', 'Job Service berhasil diperbarui.');
    }

    //FUNGSI STORE
    public function store()
    {
        $db = \Config\Database::connect();
        try {
            $db->transStart();

            $MOprService = new \Modules\Operational\Models\MOprService();
            $MOprServiceItem = new \Modules\Operational\Models\MOprServiceItem();

            // Logika Penomoran ID & No. Service
            $lastIdRow = $MOprService->selectMax('opr_service_id', 'last_id')->get()->getRow();
            $new_id = ($lastIdRow ? $lastIdRow->last_id : 0) + 1;
            $new_no = sprintf('SR-ART/%s-%s/%04d', date('m'), date('y'), $new_id);

            $schedule_uuid = $this->request->getPost('schedule_uuid');
            $refill_mr = $db->table('wr_matrequest')->where('wr_matrequest_opr_schedule_uuid', $schedule_uuid)->get()->getRow();
            $refill_mr_uuid = $refill_mr ? $refill_mr->wr_matrequest_uuid : null;

            // Simpan data utama
            $serviceUUID = Uuid::uuid4()->toString();
            $mainData = [
                'opr_service_uuid' => $serviceUUID,
                'opr_service_id'   => $new_id,
                'opr_service_no'   => $new_no,
                'opr_service_date' => date('Y-m-d'),
                'opr_service_schedule_uuid' => $schedule_uuid,
                'opr_service_cust_pic_uuid' => $this->request->getPost('cust_pic_uuid'),
                'opr_service_cust_sign_name' => $this->request->getPost('cust_sign_name'),
                'opr_service_cust_sign_position' => $this->request->getPost('cust_sign_position'),
                'opr_service_cust_pic_sign' => $this->request->getPost('cust_sign_image'),
                'opr_service_work_duration' => $this->request->getPost('work_duration'), // Jika ada
                'opr_service_status' => 'Pending', // Status awal
            ];
            $MOprService->insert($mainData);

            // Loop dan simpan item
            $items = $this->request->getPost('items');
            $item_id_counter = 1;

            if (!empty($items)) {
                foreach ($items as $item_data) {
                    $itemData = [
                        'opr_service_item_uuid' => Uuid::uuid4()->toString(),
                        'opr_service_item_id'   => $item_id_counter++,
                        'opr_service_item_service_uuid' => $serviceUUID,
                        'opr_service_matrequest_uuid' => $refill_mr_uuid,
                        'opr_service_item_inventory_uuid' => $item_data['inventory_uuid'],
                        'opr_service_item_building_uuid' => $item_data['building_uuid'],
                        'opr_service_item_room_uuid' => $item_data['room_uuid'],
                        'opr_service_item_problem' => $item_data['problem'],
                        'opr_service_item_action' => $item_data['action'],
                        'opr_service_item_operation' => $item_data['work_duration'],
                        'opr_service_item_image' => $item_data['photo_base64'] ?: null, // Ambil Base64 dari POST
                        'opr_service_item_status' => 'Pending',
                    ];
                    $MOprServiceItem->insert($itemData);
                }
            }
            
            // Update status opr_schedule menjadi 'Done'
            $db->table('opr_schedule')->where('opr_schedule_uuid', $schedule_uuid)->update(['opr_schedule_status' => 'Done']);

            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan data.']);
            } else {
                $db->transCommit();
                return $this->response->setJSON(['status' => 'success', 'message' => 'Job Service berhasil disimpan dengan No: ' . $new_no]);
            }

        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}