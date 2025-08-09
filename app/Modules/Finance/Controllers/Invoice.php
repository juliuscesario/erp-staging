<?php
namespace Modules\Finance\Controllers;
use App\Controllers\BaseController;

use Modules\Operational\Models\MInvoice;
// Call PDF 
use Dompdf\Dompdf;
use Dompdf\Options;
// Call UUID
use Ramsey\Uuid\Uuid;

class Invoice extends BaseController
{
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    //Index
    public function index()
    {
        $invoiceModel = new \Modules\Finance\Models\MInvoice();
        $data['contracts'] = $invoiceModel->getContractsForInvoicing();
        return view('Modules\Finance\Views\index', $data);
    }

    //Load Invoice Data
    public function loadInvoiceData()
    {
        $contract_uuid = $this->request->getPost('contract_uuid');
        if (!$contract_uuid) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Kontrak tidak valid.']);
        }

        $invoiceModel = new \Modules\Finance\Models\MInvoice();
        $data = [];

        // Ambil info kontrak dan kuotasi
        $contract = $this->db->table('mkt_contract')->where('mkt_contract_uuid', $contract_uuid)->get()->getRowArray();
        if($contract) {
            $quotation = $this->db->table('mkt_quotation')->where('mkt_quotation_uuid', $contract['mkt_contract_quotation_uuid'])->get()->getRowArray();
            $data['quotation'] = $quotation;
            
            // --- AWAL LOGIKA PERHITUNGAN BARU ---
            $order_items = $this->db->table('mkt_quotation_order')
                                    ->where('mkt_quotation_order_quotation_uuid', $quotation['mkt_quotation_uuid'])
                                    ->get()->getResultArray();

            $calculated_subtotal = 0;
            foreach($order_items as $item) {
                $calculated_subtotal += ($item['mkt_quotation_order_item_price'] * $item['mkt_quotation_order_item_qty']);
            }

            $data['quotation'] = $this->db->table('mkt_quotation')->where('mkt_quotation_uuid', $contract['mkt_contract_quotation_uuid'])->get()->getRowArray();
            $quotation_uuid = $data['quotation']['mkt_quotation_uuid'];

            // --- AWAL REWORK QUERY ITEM ---

            // Query 1: Ambil item 'Asset' yang terikat dengan lokasi (gedung & ruangan)
            $asset_builder = $this->db->table('mkt_quotation_order as a');
            $asset_builder->select('a.*, b.inventory_name, room.room_name, bldg.building_name');
            $asset_builder->join('m_inventory as b', 'a.mkt_quotation_order_unit_inventory_uuid = b.inventory_uuid');
            $asset_builder->join('m_room as room', 'a.mkt_quotation_order_room_uuid = room.room_uuid', 'left');
            $asset_builder->join('m_building as bldg', 'a.mkt_quotation_order_building_uuid = bldg.building_uuid', 'left');
            $asset_builder->where('a.mkt_quotation_order_quotation_uuid', $quotation_uuid);
            $asset_builder->where('b.inventory_jenis', 'Asset');
            $asset_items = $asset_builder->get()->getResultArray();

            // Query 2: Ambil item 'Retail' (seperti oil) yang tidak terikat lokasi
            $retail_builder = $this->db->table('mkt_quotation_order as a');
            $retail_builder->select('a.*, b.inventory_name');
            $retail_builder->join('m_inventory as b', 'a.mkt_quotation_order_oil_inventory_uuid = b.inventory_uuid');
            $retail_builder->where('a.mkt_quotation_order_quotation_uuid', $quotation_uuid);
            $retail_builder->where('b.inventory_jenis', 'Retail');
            $retail_builder->groupBy('a.mkt_quotation_order_oil_inventory_uuid'); // Grouping untuk memastikan hanya muncul sekali
            $retail_items = $retail_builder->get()->getResultArray();

            // Gabungkan kedua hasil query
            $data['order_items'] = array_merge($asset_items, $retail_items);
            
            // --- AWAL LOGIKA BARU: HITUNG INVOICE SEBELUMNYA ---
            $existing_invoice_count = $this->db->table('inv_invoice')
                                            ->where('inv_invoice_contract_uuid', $contract_uuid)
                                            ->countAllResults(); // Menghitung jumlah baris
            
            $data['invoice_count'] = $existing_invoice_count;
            // --- AKHIR LOGIKA BARU ---

            //CHECK TERM OF PAYMENT
            $termOfPayment = intval($quotation['mkt_quotation_term_of_payment'] ?? 1);
            if ($termOfPayment <= 0) $termOfPayment = 1;
            
            $total_per_termin = $calculated_subtotal * $termOfPayment;
            
            // Tambahkan PPN jika ada
            $ppn_persen = floatval($quotation['mkt_quotation_ppn_persen'] ?? 0);
            $ppn_total = $total_per_termin * ($ppn_persen / 100);
            
            // Kurangi diskon
            $discount = floatval($quotation['mkt_quotation_discount'] ?? 0);

            // Simpan hasil perhitungan ke dalam data yang akan dikirim
            $data['calculated_totals'] = [
                'subtotal' => $total_per_termin,
                'discount' => $discount,
                'ppn'      => $ppn_total,
                'grand_total' => $total_per_termin + $ppn_total - $discount
            ];
            // --- AKHIR LOGIKA PERHITUNGAN BARU ---
            
        }
        
        // Ambil daftar job service yang bisa ditagih (logika ini tetap sama)
        $services = $invoiceModel->getUninvoicedServices($contract_uuid);
         // Logika baru untuk mengelompokkan Job Service
        // --- AWAL REWORK LOGIKA PENGELOMPOKAN JOB SERVICE ---

        $data['invoice_batches'] = [];
        $data['incomplete_services'] = []; 
        $termOfPayment = intval($data['quotation']['mkt_quotation_term_of_payment'] ?? 1);

        // Failsafe untuk memastikan pembagi tidak nol atau negatif
        if ($termOfPayment <= 0) {
            $termOfPayment = 1;
        }

        if (!empty($services)) {
            for ($i = 0; $i < count($services); $i += $termOfPayment) {
                $batch = array_slice($services, $i, $termOfPayment);
                
                if (count($batch) === $termOfPayment) { // Batch lengkap
                    $service_uuids = array_column($batch, 'opr_service_uuid');
                    $periods = array_column($batch, 'opr_schedule_period_run');
                    $data['invoice_batches'][] = [
                        'label' => 'Invoice Termin untuk Periode ' . implode(' & ', $periods),
                        'value' => implode(',', $service_uuids)
                    ];
                } else { // Batch tidak lengkap
                    $data['incomplete_services'] = array_merge($data['incomplete_services'], $batch);
                }
            }
        }
        
        // --- AKHIR REWORK ---

        return $this->response->setJSON($data);
    }


    // Nanti kita akan tambahkan fungsi store() di sini
    // app/Modules/Finance/Controllers/Invoice.php

    public function store()
    {
        // 1. Muat helper 'terbilang' di awal
        helper('terbilang');

        // 2. Ambil dan validasi data JSON dari request
        $json = json_decode($this->request->getBody());
        if (!$json || empty($json->contract_uuid) || empty($json->services_batch) || !isset($json->invoice_date)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Data yang dikirim tidak lengkap.']);
        }

        try {
            $this->db->transStart();

            $invoiceModel = new \Modules\Finance\Models\MInvoice();

            // 3. Validasi data pendukung dari database
            $contract = $this->db->table('mkt_contract')->where('mkt_contract_uuid', $json->contract_uuid)->get()->getRow();
            if (!$contract) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Data Kontrak tidak ditemukan.']);
            }

            $quotation = $this->db->table('mkt_quotation')->where('mkt_quotation_uuid', $contract->mkt_contract_quotation_uuid)->get()->getRow();
            if (!$quotation) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Data Kuotasi terkait tidak ditemukan.']);
            }

            $billing_info = $this->db->table('m_cust_bill')->where(['cust_bill_cust_uuid' => $contract->mkt_contract_cust_uuid, 'cust_bill_default' => 'Ya'])->get()->getRow();
            if (!$billing_info) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Informasi penagihan (Billing) default untuk customer ini belum diatur.']);
            }
            
            // 4. Logika Penomoran Invoice
            $lastIdRow = $invoiceModel->selectMax('inv_invoice_id', 'last_id')->get()->getRow();
            $new_id = ($lastIdRow ? $lastIdRow->last_id : 0) + 1;
            $new_no = sprintf('INV-ART/%s-%s/%04d', date('m'), date('y'), $new_id);

            // 5. Panggil helper untuk "Terbilang"
            $terbilang_text = number_to_words($json->grand_total_value);

            // 6. Siapkan & simpan data utama ke tabel inv_invoice
            $invoiceUUID = Uuid::uuid4()->toString();
            $invoiceData = [
                'inv_invoice_uuid' => $invoiceUUID,
                'inv_invoice_id'   => $new_id,
                'inv_invoice_no'   => $new_no,
                'inv_invoice_date' => $json->invoice_date,
                'inv_invoice_contract_uuid' => $json->contract_uuid,
                'inv_invoice_cust_uuid' => $contract->mkt_contract_cust_uuid,
                'inv_invoice_cust_bill_uuid' => $billing_info->cust_bill_uuid,
                'inv_invoice_cust_bill_tax_uuid' => $billing_info->cust_bill_tax_uuid,
                'inv_invoice_payment_type' => $quotation->mkt_quotation_payment_method,
                'inv_invoice_subtotal' => $quotation->mkt_quotation_subtotal,
                'inv_invoice_discount' => $quotation->mkt_quotation_discount,
                'inv_invoice_ppn_total' => $quotation->mkt_quotation_ppn_total,
                'inv_invoice_terbilang' => $terbilang_text,
                'inv_invoice_grand_total' => $json->grand_total_value,
                'inv_invoice_status' => 'Unpaid',
            ];
            $invoiceModel->insert($invoiceData);

            // 7. Loop, simpan item, dan kunci Job Service
            foreach ($json->services_batch as $batch) {
                $service_uuids = explode(',', $batch);
                foreach($service_uuids as $service_uuid) {
                    // Ambil tanggal dari service yang akan disimpan
                    $service = $this->db->table('opr_service')->where('opr_service_uuid', $service_uuid)->get()->getRow();

                    $this->db->table('inv_invoice_item')->insert([
                        'inv_invoice_item_uuid' => Uuid::uuid4()->toString(),
                        'inv_invoice_item_invoice_uuid' => $invoiceUUID,
                        'inv_invoice_item_service_uuid' => $service_uuid,
                        'inv_invoice_item_service_date' => $service ? $service->opr_service_date : null
                    ]);
                    
                    // Kunci Job Service agar tidak bisa diedit/di-invoice lagi
                    $this->db->table('opr_service')->where('opr_service_uuid', $service_uuid)->update(['opr_service_locked' => 'Ya','opr_service_invoice_uuid' => $invoiceUUID]);
                }
            }
            
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan invoice karena kesalahan database.']);
            } else {
                return $this->response->setJSON(['status' => 'success', 'message' => 'Invoice berhasil dibuat dengan No: ' . $new_no]);
            }

        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    //Print PDF
    public function printPdf($invoice_uuid)
    {
        helper('terbilang');
        $db = \Config\Database::connect();
        $data = [];

        $invoiceModel = new \Modules\Finance\Models\MInvoice();
        $data = $invoiceModel->getInvoiceDetail($invoice_uuid);

        // 1. Ambil data utama invoice (query ini sudah benar)

        if (!$data['invoice']) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Invoice tidak ditemukan');
        }

        // --- AKHIR LOGIKA BARU ---
        
        // 3. Ambil data bank default
        $data['bank'] = $db->table('m_bank')->where('bank_is_default', 'Ya')->get()->getRowArray();

        // 4. (Placeholder) Logika untuk "Terbilang"
        // $data['terbilang'] = fungsi_terbilang($data['invoice']->inv_invoice_grand_total);
        // 2. Panggil fungsi dan simpan hasilnya
        $data['terbilang'] = number_to_words($data['invoice']['inv_invoice_grand_total']);
         // Buat footer
        $address_parts = [
            $data['invoice']['branch_alamat'] ?? null,
            $data['invoice']['branch_kelurahan'] ?? null,
            isset($data['invoice']['branch_kecamatan']) ? 'Kec. ' . $data['invoice']['branch_kecamatan'] : null,
            $data['invoice']['branch_kabupaten'] ?? null,
            $data['invoice']['branch_provinsi'] ?? null,
        ];
        $full_address = implode(', ', array_filter($address_parts));
        $data['full_address'] = $full_address. ' ' . ($data['invoice']['branch_kode_pos'] ?? '') . ', ' . ($data['invoice']['branch_negara'] ?? '') . ' Telp ' . ($data['invoice']['branch_phone1'] ?? '');

        $html = view('Modules\Finance\Views\print_invoice', $data);
        
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $dompdf->stream($data['invoice']['inv_invoice_no'] . ".pdf", ["Attachment" => 0]);
    }

    // app/Modules/Finance/Controllers/Invoice.php
    //List
    public function list()
    {
        // Query untuk mengambil data invoice dan nama customer terkait
        $builder = $this->db->table('inv_invoice as a');
        $builder->select('a.inv_invoice_uuid, a.inv_invoice_no, a.inv_invoice_date, a.inv_invoice_status, b.cust_name');
        $builder->join('m_cust as b', 'a.inv_invoice_cust_uuid = b.cust_uuid', 'left');
        $builder->orderBy('a.inv_invoice_id', 'DESC'); // Tampilkan yang terbaru di atas

        $data['invoices'] = $builder->get()->getResultArray();

        return view('Modules\Finance\Views\list', $data);
    }

    public function detail($invoice_uuid)
    {
        $invoiceModel = new \Modules\Finance\Models\MInvoice();
        $data = $invoiceModel->getInvoiceDetail($invoice_uuid);

        if (empty($data['invoice'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Invoice tidak ditemukan');
        }

        return view('Modules\Finance\Views\detail', $data);
    }

    
    // ADD THIS NEW METHOD FOR THE EDIT PAGE
    public function edit($invoice_uuid)
    {
        helper('terbilang');
        $invoiceModel = new \Modules\Finance\Models\MInvoice();
        $data = $invoiceModel->getInvoiceDetail($invoice_uuid);

        if (empty($data['invoice'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Invoice tidak ditemukan');
        }

        // IMPORTANT: Only allow editing for 'Unpaid' invoices
        if ($data['invoice']['inv_invoice_status'] !== 'Unpaid') {
            return redirect()->to(site_url('finance/invoice/list'))->with('error', 'Invoice yang sudah lunas tidak dapat diubah.');
        }

        return view('Modules\Finance\Views\edit', $data);
    }

    // ADD THIS NEW METHOD TO HANDLE THE UPDATE
    public function update($invoice_uuid)
    {
        helper('terbilang');
        $invoiceModel = new \Modules\Finance\Models\MInvoice();
        $invoice = $invoiceModel->find($invoice_uuid);

        if (!$invoice || $invoice['inv_invoice_status'] !== 'Unpaid') {
            return redirect()->to(site_url('finance/invoice/list'))->with('error', 'Invoice tidak ditemukan atau sudah lunas.');
        }

        // Get data from form
        $invoice_date = $this->request->getPost('inv_invoice_date');
        $discount = (float) $this->request->getPost('inv_invoice_discount');
        $apply_tax = $this->request->getPost('apply_tax');

        // Recalculate totals
        $subtotal = (float) $invoice['inv_invoice_subtotal'];
        $ppn_total = 0;
        
        // Sesuai aturan: PPN 11% dari (Subtotal - Diskon)
        if ($apply_tax) {
            $ppn_total = ($subtotal - $discount) * 0.11;
        }

        $grand_total = $subtotal - $discount + $ppn_total;
        $terbilang = number_to_words($grand_total);

        // Prepare data for update
        $updateData = [
            'inv_invoice_date'      => $invoice_date,
            'inv_invoice_due_date'  => date('Y-m-d', strtotime($invoice_date . ' +14 days')), // Asumsi jatuh tempo 14 hari
            'inv_invoice_discount'  => $discount,
            'inv_invoice_ppn_total' => $ppn_total,
            'inv_invoice_grand_total' => $grand_total,
            'inv_invoice_terbilang' => $terbilang,
        ];
        
        // Update the database
        $invoiceModel->update($invoice_uuid, $updateData);

        return redirect()->to(site_url('finance/invoice/detail/' . $invoice_uuid))->with('success', 'Invoice berhasil diperbarui.');
    }
}