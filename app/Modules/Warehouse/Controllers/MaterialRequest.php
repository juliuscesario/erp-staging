<?php

namespace Modules\Warehouse\Controllers;

use App\Controllers\BaseController;
use Ramsey\Uuid\Uuid;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Modules\Warehouse\Models\MatrequestModel;
use Modules\Warehouse\Models\MatrequestItemModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class MaterialRequest extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('wr_matrequest as a');
        $builder->select('a.wr_matrequest_uuid, a.wr_matrequest_no, a.wr_matrequest_date, a.wr_matrequest_status, c.mkt_contract_no, c.mkt_contract_uuid');
        $builder->join('opr_schedule as b', 'a.wr_matrequest_opr_schedule_uuid = b.opr_schedule_uuid', 'left');
        $builder->join('mkt_contract as c', 'b.opr_schedule_contract_uuid = c.mkt_contract_uuid', 'left');
        $builder->orderBy('a.wr_matrequest_id', 'DESC');
        $data['material_requests'] = $builder->get()->getResultArray();
        return view('Modules\Warehouse\Views\list', $data);
    }

    public function create($contractUUID)
    {
        $db = \Config\Database::connect();
        $subquery = $db->table('wr_matrequest')->select('wr_matrequest_opr_schedule_uuid');
        $builder = $db->table('opr_schedule');
        $builder->select('opr_schedule_uuid, opr_schedule_id, opr_schedule_date');
        $builder->where('opr_schedule_contract_uuid', $contractUUID)->whereNotIn('opr_schedule_uuid', $subquery);
        $builder->orderBy('opr_schedule_date', 'ASC');
        $data['schedules'] = $builder->get()->getResultArray();
        
        $karyawanBuilder = $db->table('m_karyawan')->select('kar_uuid, kar_name')->where('kar_aktif', 'Ya');
        $data['karyawan'] = $karyawanBuilder->get()->getResultArray();
        
        $data['new_matrequest_uuid'] = Uuid::uuid4()->toString();
        $data['contract_uuid'] = $contractUUID; 
        return view('Modules\Warehouse\Views\create', $data);
    }

    public function detail($uuid)
    {
        $db = \Config\Database::connect();
        $main_builder = $db->table('wr_matrequest as a');
        $main_builder->select('a.*, c.mkt_contract_no, b.opr_schedule_date, k1.kar_name as nama_pemberi, k2.kar_name as nama_penerima');
        $main_builder->join('opr_schedule as b', 'a.wr_matrequest_opr_schedule_uuid = b.opr_schedule_uuid', 'left');
        $main_builder->join('mkt_contract as c', 'b.opr_schedule_contract_uuid = c.mkt_contract_uuid', 'left');
        $main_builder->join('m_karyawan as k1', 'a.wr_matrequest_kar_uuid_sign1 = k1.kar_uuid', 'left');
        $main_builder->join('m_karyawan as k2', 'a.wr_matrequest_kar_uuid_sign2 = k2.kar_uuid', 'left');
        $main_builder->where('a.wr_matrequest_uuid', $uuid);
        $data['mr'] = $main_builder->get()->getRowArray();

        if (!$data['mr']) throw new \CodeIgniter\Exceptions\PageNotFoundException('Material Request tidak ditemukan');

        $item_builder = $db->table('wr_matrequest_item as a');
        $item_builder->select('a.*, b.inventory_kode, b.inventory_name');
        $item_builder->join('m_inventory as b', 'a.wr_matrequest_item_inventory_uuid = b.inventory_uuid', 'left');
        $item_builder->where('a.wr_matrequest_item_matrequest_uuid', $uuid);
        $items_raw = $item_builder->get()->getResultArray();
        
        $data['items'] = [];
        $writer = new PngWriter();
        foreach($items_raw as $item) {
            $decoded_data = json_decode($item['wr_matrequest_item_batch_uuid'], true);
            if(json_last_error() === JSON_ERROR_NONE && $decoded_data) {
                // Ambil data lokasi jika ada
                $item['building_name'] = isset($decoded_data['building_uuid']) ? $db->table('m_building')->where('building_uuid', $decoded_data['building_uuid'])->get()->getRow('building_name') : 'Gudang';
                $item['room_name'] = isset($decoded_data['room_uuid']) ? $db->table('m_room')->where('room_uuid', $decoded_data['room_uuid'])->get()->getRow('room_name') : '';
                
                // Generate ulang QR image dari link yang disimpan
                if(isset($decoded_data['qr_link'])) {
                    $qrCode = new QrCode($decoded_data['qr_link']);
                    $item['qrcode_image'] = $writer->write($qrCode)->getDataUri();
                } else {
                    $item['qrcode_image'] = null;
                }
            } else {
                $item['building_name'] = 'Gudang';
                $item['room_name'] = '';
                $item['qrcode_image'] = null;
            }
            $data['items'][] = $item;
        }
        
        $data['cont'] = 'warehouse/materialrequest';
        return view('Modules\Warehouse\Views\detail', $data);
    }
    
    // Fungsi AJAX untuk generate item list
    public function generate_items()
    {
        $schedule_uuid = $this->request->getPost('schedule_uuid');
        $new_matrequest_uuid = $this->request->getPost('matrequest_uuid');
        $db = \Config\Database::connect();

        $schedule = $db->table('opr_schedule')->where('opr_schedule_uuid', $schedule_uuid)->get()->getRow();
        if (!$schedule) return $this->response->setJSON([]);
        
        $period_run = $schedule->opr_schedule_period_run;

        $item_builder = $db->table('opr_schedule as a');
        $item_builder->select([
            'd.mkt_quotation_order_unit_inventory_uuid', 'd.mkt_quotation_order_oil_inventory_uuid',
            'd.mkt_quotation_order_item_qty', 'd.mkt_quotation_order_building_uuid',
            'd.mkt_quotation_order_room_uuid', 'b.mkt_contract_uuid'
        ]);
        $item_builder->join('mkt_contract as b', 'a.opr_schedule_contract_uuid = b.mkt_contract_uuid');
        $item_builder->join('mkt_quotation as c', 'b.mkt_contract_quotation_uuid = c.mkt_quotation_uuid');
        $item_builder->join('mkt_quotation_order as d', 'c.mkt_quotation_uuid = d.mkt_quotation_order_quotation_uuid');
        $item_builder->where('a.opr_schedule_uuid', $schedule_uuid);
        $quotation_items = $item_builder->get()->getResultArray();

        if (empty($quotation_items)) return $this->response->setJSON([]);

        $processed_items = [];
        $grouped_non_assets = [];

        foreach ($quotation_items as $q_item) {
            $unit_uuid = $q_item['mkt_quotation_order_unit_inventory_uuid'];
            if (!empty($unit_uuid)) {
                $unit_details = $db->table('m_inventory')->where('inventory_uuid', $unit_uuid)->get()->getRowArray();
                
                $building_name = $db->table('m_building')->select('building_name')->where('building_uuid', $q_item['mkt_quotation_order_building_uuid'])->get()->getRow('building_name') ?? '';
                $room_name = $db->table('m_room')->select('room_name')->where('room_uuid', $q_item['mkt_quotation_order_room_uuid'])->get()->getRow('room_name') ?? '';
                $lokasi = $building_name . ' / ' . $room_name;

                if ($unit_details && $unit_details['inventory_jenis'] === 'Asset' && $period_run == 1) {
                    for ($i = 0; $i < (int)$q_item['mkt_quotation_order_item_qty']; $i++) {
                        $item_instance_uuid = Uuid::uuid4()->toString();
                        $qr_link = site_url("qrcode/verify/{$q_item['mkt_contract_uuid']}/{$new_matrequest_uuid}/{$item_instance_uuid}");
                        
                        $writer = new PngWriter();
                        $qrCode = new QrCode(
                            $qr_link, new Encoding('UTF-8'), ErrorCorrectionLevel::High,
                            300, 10, null, new Color(0, 0, 0), new Color(255, 255, 255)
                        );
                        
                        $qr_base64 = $writer->write($qrCode)->getDataUri();
                        
                        $processed_items[] = [
                            'inventory_uuid' => $unit_uuid,
                            'building_uuid' => $q_item['mkt_quotation_order_building_uuid'],
                            'room_uuid' => $q_item['mkt_quotation_order_room_uuid'],
                            'kode' => $unit_details['inventory_kode'], 
                            'nama' => $unit_details['inventory_name'], 
                            'qty' => 1, 'lokasi' => $lokasi, 
                            'qrcode' => $qr_base64, 'qrcode_link' => $qr_link,
                        ];
                    }
                }
            }

            $oil_uuid = $q_item['mkt_quotation_order_oil_inventory_uuid'];
            if (!empty($oil_uuid)) {
                if (!isset($grouped_non_assets[$oil_uuid])) {
                     $oil_details = $db->table('m_inventory')->where('inventory_uuid', $oil_uuid)->get()->getRowArray();
                     if($oil_details) {
                        $grouped_non_assets[$oil_uuid] = ['inventory_uuid' => $oil_uuid, 'kode' => $oil_details['inventory_kode'], 'nama' => $oil_details['inventory_name'], 'qty' => 0, 'lokasi' => 'Gudang', 'qrcode' => null, 'qrcode_link' => null, 'building_uuid' => null, 'room_uuid' => null];
                     }
                }
                if (isset($grouped_non_assets[$oil_uuid])) {
                    $grouped_non_assets[$oil_uuid]['qty'] += $q_item['mkt_quotation_order_item_qty'];
                }
            }
        }
        
        $final_items = array_merge($processed_items, array_values($grouped_non_assets));
        usort($final_items, fn($a, $b) => ($a['qrcode'] === null) - ($b['qrcode'] === null));

        return $this->response->setJSON($final_items);
    }
    
   public function store()
    {
        $json = json_decode($this->request->getBody());
        $db = \Config\Database::connect();
        try {
            $db->transStart();
            $matRequestModel = new MatrequestModel();
            $matRequestItemModel = new MatrequestItemModel();

            $lastId = $matRequestModel->selectMax('wr_matrequest_id', 'last_id')->get()->getRow('last_id');
            $new_id = ($lastId ?? 0) + 1;
            $new_no = sprintf('MR-ART/%s-%s/%04d', date('m'), date('y'), $new_id);
            
            $mainData = [
                'wr_matrequest_uuid' => $json->matrequest_uuid, 'wr_matrequest_id' => $new_id, 'wr_matrequest_no' => $new_no,
                'wr_matrequest_date' => date('Y-m-d'), 'wr_matrequest_opr_schedule_uuid' => $json->schedule_uuid,
                'wr_matrequest_kar_uuid_sign1' => $json->kar_uuid_1, 'wr_matrequest_sign1' => $json->signature_data_1,
                'wr_matrequest_kar_uuid_sign2' => $json->kar_uuid_2, 'wr_matrequest_sign2' => $json->signature_data_2,
                'wr_matrequest_status' => 'Pending'
            ];
            $matRequestModel->insert($mainData);

            $item_id_counter = 1;
            foreach ($json->items as $item) {
                // **PERBAIKAN UTAMA DI SINI**
                // Gabungkan data lokasi dan QR link ke dalam satu JSON
                $batch_data = null;
                if (!empty($item->building_uuid) || !empty($item->qrcode_link)) {
                    $temp_data = [];
                    if(!empty($item->building_uuid)) $temp_data['building_uuid'] = $item->building_uuid;
                    if(!empty($item->room_uuid)) $temp_data['room_uuid'] = $item->room_uuid;
                    if(!empty($item->qrcode_link)) $temp_data['qr_link'] = $item->qrcode_link;
                    $batch_data = json_encode($temp_data);
                }
                
                $itemData = [
                    'wr_matrequest_item_matrequest_uuid' => $json->matrequest_uuid, 'wr_matrequest_item_id' => $item_id_counter++,
                    'wr_matrequest_item_inventory_uuid' => $item->inventory_uuid,
                    'wr_matrequest_item_batch_uuid' => $batch_data, // Simpan JSON di sini
                    'wr_matrequest_item_item_qty' => $item->qty
                ];
                // Hapus kolom qrcode_link dan qrcode_image dari array data
                // karena tidak ada di tabel.
                $matRequestItemModel->insert($itemData);
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan data karena transaksi database gagal.']);
            } else {
                $db->transCommit();
                return $this->response->setJSON(['status' => 'success']);
            }
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function printPdf($uuid)
    {
        $db = \Config\Database::connect();
        $main_builder = $db->table('wr_matrequest as a');
        $main_builder->select('a.*, c.mkt_contract_no, b.opr_schedule_date, k1.kar_name as nama_pemberi, k2.kar_name as nama_penerima');
        $main_builder->join('opr_schedule as b', 'a.wr_matrequest_opr_schedule_uuid = b.opr_schedule_uuid', 'left');
        $main_builder->join('mkt_contract as c', 'b.opr_schedule_contract_uuid = c.mkt_contract_uuid', 'left');
        $main_builder->join('m_karyawan as k1', 'a.wr_matrequest_kar_uuid_sign1 = k1.kar_uuid', 'left');
        $main_builder->join('m_karyawan as k2', 'a.wr_matrequest_kar_uuid_sign2 = k2.kar_uuid', 'left');
        $main_builder->where('a.wr_matrequest_uuid', $uuid);
        $data['mr'] = $main_builder->get()->getRow();

        $item_builder = $db->table('wr_matrequest_item as a');
        $item_builder->select('a.*, b.inventory_kode, b.inventory_name');
        $item_builder->join('m_inventory as b', 'a.wr_matrequest_item_inventory_uuid = b.inventory_uuid', 'left');
        $item_builder->where('a.wr_matrequest_item_matrequest_uuid', $uuid);
        $items_raw = $item_builder->get()->getResult('array');
        
        $data['items'] = [];
        foreach($items_raw as $item) {
            $location_data = json_decode($item['wr_matrequest_item_batch_uuid'], true);
            if(json_last_error() === JSON_ERROR_NONE && isset($location_data['building_uuid'])) {
                $item['lokasi'] = $db->table('m_building')->where('building_uuid', $location_data['building_uuid'])->get()->getRow('building_name') . ' / ' . $db->table('m_room')->where('room_uuid', $location_data['room_uuid'])->get()->getRow('room_name');
            } else {
                $item['lokasi'] = 'Gudang';
            }
            $data['items'][] = $item;
        }

        $html = view('Modules\Warehouse\Views\print_detail', $data);
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($data['mr']->wr_matrequest_no . ".pdf", ["Attachment" => 0]);
    }

    public function printSticker($request_uuid, $item_id)
    {
        $db = \Config\Database::connect();
        $item_builder = $db->table('wr_matrequest_item as a');
        $item_builder->select('a.wr_matrequest_item_batch_uuid, b.inventory_kode');
        $item_builder->join('m_inventory as b', 'a.wr_matrequest_item_inventory_uuid = b.inventory_uuid', 'left');
        $item_builder->where('a.wr_matrequest_item_matrequest_uuid', $request_uuid);
        $item_builder->where('a.wr_matrequest_item_id', $item_id);
        $item_data = $item_builder->get()->getRow();

        if (!$item_data) throw new \CodeIgniter\Exceptions\PageNotFoundException('Item tidak ditemukan');

        $decoded = json_decode($item_data->wr_matrequest_item_batch_uuid, true);
        if(json_last_error() !== JSON_ERROR_NONE || !isset($decoded['qr_link'])) {
             throw new \CodeIgniter\Exceptions\PageNotFoundException('Link QR Code untuk item ini tidak ditemukan.');
        }

        $writer = new PngWriter();
        $qrCode = new QrCode($decoded['qr_link']);
        $qr_base64 = $writer->write($qrCode)->getDataUri();

        $data['item_qrcode_image'] = $qr_base64;
        $data['item_kode'] = $item_data->inventory_kode;

        $html = view('Modules\Warehouse\Views\print_sticker', $data);
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 283, 283]);
        $dompdf->render();
        $dompdf->stream("Stiker-" . $data['item_kode'] . ".pdf", ["Attachment" => 0]);
    }
}