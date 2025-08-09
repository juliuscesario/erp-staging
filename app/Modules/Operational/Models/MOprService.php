<?php
namespace Modules\Operational\Models;
use CodeIgniter\Model;

class MOprService extends Model
{
    protected $table         = 'opr_service';
    protected $primaryKey    = 'opr_service_uuid';
    protected $allowedFields = [
        'opr_service_uuid',
        'opr_service_id',
        'opr_service_no',
        'opr_service_date',
        'opr_service_schedule_uuid',
        'opr_service_cust_pic_uuid',
        'opr_service_cust_sign_name',
        'opr_service_cust_sign_position',
        'opr_service_cust_pic_sign',
        'opr_service_work_duration',
        'opr_service_status',
        'opr_service_created_by'
    ];

    /**
     * Fungsi "super" untuk mengambil semua data yang dibutuhkan
     * untuk halaman create, detail, dan edit Job Service.
     *
     * @param string $uuid UUID bisa berasal dari opr_schedule atau opr_service
     * @param string $type Tipe UUID: 'schedule' atau 'service'
     * @return array
     */
    public function getServiceDataSet(string $uuid, string $type = 'schedule'): array
    {
        $data = [
            'main' => null,
            'items' => [], // Untuk detail & edit
            'assets' => [], // Untuk create
            'pics' => []
        ];

        // Langkah 1: Bangun query utama untuk mengambil semua info header
        $builder = $this->db->table('opr_schedule as s');
        $builder->select('
            s.opr_schedule_date, s.opr_schedule_period_run, s.opr_schedule_period_total, 
            con.mkt_contract_uuid, con.mkt_contract_no, con.mkt_contract_quotation_uuid,
            cust.cust_uuid, cust.cust_name, 
            br.branch_name,
            bldg.building_name, bldg.building_add1, bldg.building_kelurahan, bldg.building_kecamatan, bldg.building_kabupaten, bldg.building_kode_pos,
            service.opr_service_uuid, service.opr_service_no, service.opr_service_cust_pic_uuid, service.opr_service_cust_sign_position, service.opr_service_cust_pic_sign, service.opr_service_work_duration,
            pic.cust_pic_kontak as cust_pic_name
        ');
        $builder->join('mkt_contract as con', 's.opr_schedule_contract_uuid = con.mkt_contract_uuid');
        $builder->join('m_cust as cust', 'con.mkt_contract_cust_uuid = cust.cust_uuid', 'left');
        $builder->join('m_branch as br', 'con.mkt_contract_branch_uuid = br.branch_uuid', 'left');
        $builder->join('mkt_quotation as q', 'con.mkt_contract_quotation_uuid = q.mkt_quotation_uuid', 'left');
        $builder->join('m_building as bldg', 'FIND_IN_SET(bldg.building_uuid, q.mkt_quotation_building_uuid)', 'left');
        $builder->join('opr_service as service', 's.opr_schedule_uuid = service.opr_service_schedule_uuid', 'left');
        $builder->join('m_cust_pic as pic', 'service.opr_service_cust_pic_uuid = pic.cust_pic_uuid', 'left');

        // Tentukan filter berdasarkan tipe UUID
        if ($type === 'schedule') {
            $builder->where('s.opr_schedule_uuid', $uuid);
        } else { // type === 'service'
            $builder->where('service.opr_service_uuid', $uuid);
        }
        $builder->groupBy('s.opr_schedule_uuid');
        $data['main'] = $builder->get()->getRowArray();

        // Langkah 2: Jika data header ditemukan, lanjutkan
        if ($data['main']) {
            $data['pics'] = $this->db->table('m_cust_pic')->where('cust_pic_cust_uuid', $data['main']['cust_uuid'])->get()->getResultArray();

            if ($type === 'schedule') {
                // Untuk halaman create, ambil checklist aset dari MR periode 1
                $data['assets'] = $this->getAssetChecklist($data['main']['mkt_contract_uuid'], $data['main']['mkt_contract_quotation_uuid']);
            } else {
                // Untuk halaman detail/edit, ambil item yang sudah tersimpan
                $item_builder = $this->db->table('opr_service_item as item');
                $item_builder->select('item.*, inv.inventory_kode, inv.inventory_name, room.room_name, bldg.building_name');
                $item_builder->join('m_inventory as inv', 'item.opr_service_item_inventory_uuid = inv.inventory_uuid', 'left');
                $item_builder->join('m_building as bldg', 'item.opr_service_item_building_uuid = bldg.building_uuid', 'left');
                $item_builder->join('m_room as room', 'item.opr_service_item_room_uuid = room.room_uuid', 'left');
                $item_builder->where('item.opr_service_item_service_uuid', $uuid);
                $data['items'] = $item_builder->get()->getResultArray(); 
            }
        }

        return $data;
    }
    
    // Fungsi private untuk mengambil checklist, hanya dipakai di dalam model ini
    private function getAssetChecklist($contract_uuid, $quotation_uuid)
    {
        //CHECK INSTALLATION SCHEDULE
        $installation_schedule = $this->db->table('opr_schedule')
                                          ->where(['opr_schedule_contract_uuid' => $contract_uuid, 'opr_schedule_period_run' => 1])
                                          ->get()->getRow();
        if (!$installation_schedule) return [];
        //CHECK MATERIAL REQUEST
        $material_request = $this->db->table('wr_matrequest')
                                     ->where('wr_matrequest_opr_schedule_uuid', $installation_schedule->opr_schedule_uuid)
                                     ->get()->getRow();
        if (!$material_request) return [];

        $asset_builder = $this->db->table('wr_matrequest_item as mri');
        $asset_builder->select('mri.wr_matrequest_item_inventory_uuid, inv.inventory_kode, inv.inventory_name, room.room_name, qo.mkt_quotation_order_building_uuid, qo.mkt_quotation_order_room_uuid');
        $asset_builder->join('m_inventory as inv', 'mri.wr_matrequest_item_inventory_uuid = inv.inventory_uuid');
        $asset_builder->join('mkt_quotation_order as qo', 'inv.inventory_uuid = qo.mkt_quotation_order_unit_inventory_uuid', 'left');
        $asset_builder->join('m_room as room', 'qo.mkt_quotation_order_room_uuid = room.room_uuid', 'left');
        $asset_builder->where('mri.wr_matrequest_item_matrequest_uuid', $material_request->wr_matrequest_uuid);
        $asset_builder->where('qo.mkt_quotation_order_quotation_uuid', $quotation_uuid);
        $asset_builder->where('inv.inventory_jenis', 'Asset');
        $asset_builder->groupBy('mri.wr_matrequest_item_inventory_uuid');
        
        return $asset_builder->get()->getResultArray();
    }

    //Build Data Table Query
    private function _buildDataTableQuery($searchValue = null)
    {
        $builder = $this->db->table('opr_service as a');
        $builder->select('a.opr_service_uuid, a.opr_service_no, a.opr_service_date, a.opr_service_status, c.cust_name, s.opr_schedule_period_run, s.opr_schedule_period_total');
        $builder->join('opr_schedule as s', 'a.opr_service_schedule_uuid = s.opr_schedule_uuid', 'left');
        $builder->join('mkt_contract as con', 's.opr_schedule_contract_uuid = con.mkt_contract_uuid', 'left');
        $builder->join('m_cust as c', 'con.mkt_contract_cust_uuid = c.cust_uuid', 'left');

        if ($searchValue) {
            $builder->groupStart();
            $builder->like('a.opr_service_no', $searchValue);
            $builder->orLike('c.cust_name', $searchValue);
            $builder->groupEnd();
        }
        return $builder;
    }

    public function getDataTable($start, $length, $searchValue, $order)
    {
        $builder = $this->_buildDataTableQuery($searchValue);

        if ($order && count($order)) {
            $column = $order[0]['column'];
            $dir = $order[0]['dir'];
            $sortableColumns = ['opr_service_no', 'opr_service_date', 'cust_name', 'opr_schedule_period_run', 'opr_service_status'];
            $builder->orderBy($sortableColumns[$column], $dir);
        } else {
            $builder->orderBy('a.opr_service_id', 'DESC');
        }

        if ($length != -1) {
            $builder->limit($length, $start);
        }
        return $builder->get()->getResultArray();
    }

    public function countFilteredData($searchValue)
    {
        $builder = $this->_buildDataTableQuery($searchValue);
        return $builder->countAllResults();
    }

    public function countAllData()
    {
        $builder = $this->db->table($this->table);
        return $builder->countAllResults();
    }
}