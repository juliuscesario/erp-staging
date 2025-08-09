<?php
namespace Modules\Finance\Models;
use CodeIgniter\Model;

class MInvoice extends Model
{
    protected $table = 'inv_invoice';
    // Lengkapi properti lain sesuai kebutuhan
    protected $primaryKey = 'inv_invoice_uuid';
    protected $allowedFields = [
        'inv_invoice_uuid', 'inv_invoice_id', 'inv_invoice_no', 'inv_invoice_date', 
        'inv_invoice_due_date', 'inv_invoice_contract_uuid', 'inv_invoice_cust_uuid', 
        'inv_invoice_cust_bill_uuid', 'inv_invoice_cust_bill_tax_uuid', 'inv_invoice_payment_type', 
        'inv_invoice_subtotal', 'inv_invoice_discount', 'inv_invoice_ppn_total', 
        'inv_invoice_grand_total', 'inv_invoice_terbilang', 'inv_invoice_status', 
        'inv_invoice_created_by'
    ];

    /**
     * Mengambil daftar kontrak yang memiliki Job Service 'Done' & belum di-lock.
     */
    public function getContractsForInvoicing()
    {
        $builder = $this->db->table('mkt_contract as a');
        $builder->select('a.mkt_contract_uuid, a.mkt_contract_no, b.cust_name');
        $builder->join('m_cust as b', 'a.mkt_contract_cust_uuid = b.cust_uuid', 'left');
        // Langsung join ke schedule dan service
        $builder->join('opr_schedule as s', 's.opr_schedule_contract_uuid = a.mkt_contract_uuid');
        $builder->join('opr_service as os', 'os.opr_service_schedule_uuid = s.opr_schedule_uuid');
        
        // Terapkan filter
        $builder->where('os.opr_service_status', 'Done');
        $builder->where('os.opr_service_locked', 'Tidak');
        
        // Pastikan setiap kontrak hanya muncul sekali
        $builder->groupBy('a.mkt_contract_uuid');
        $builder->orderBy('a.mkt_contract_no', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Mengambil daftar Job Service yang siap ditagih untuk kontrak tertentu.
     */
    public function getUninvoicedServices($contract_uuid)
    {
        $builder = $this->db->table('opr_service as a');
        $builder->select('a.opr_service_uuid, a.opr_service_no, a.opr_service_date, s.opr_schedule_period_run');
        $builder->join('opr_schedule as s', 'a.opr_service_schedule_uuid = s.opr_schedule_uuid');
        $builder->where('s.opr_schedule_contract_uuid', $contract_uuid);
        $builder->where('a.opr_service_status', 'Done');
        $builder->where('a.opr_service_locked', 'Tidak');
        $builder->orderBy('s.opr_schedule_period_run', 'ASC'); // Urutkan berdasarkan periode
        return $builder->get()->getResultArray();
    }

    // app/Modules/Finance/Models/MInvoice.php

    public function getInvoiceDetail($invoice_uuid)
    {
        $data = [];

        // 1. Get the main invoice data (this part is already correct)
        $main_builder = $this->db->table('inv_invoice as a');
        $main_builder->select('a.*, b.cust_name, bill.cust_bill_email, tax.bill_tax_npwp, tax.bill_tax_alamat, con.mkt_contract_no, con.mkt_contract_quotation_uuid,
         bldg.building_name, bldg.building_add1, bldg.building_kelurahan, bldg.building_kecamatan, bldg.building_kabupaten, bldg.building_kode_pos,
            br.branch_alamat, br.branch_kelurahan, br.branch_kecamatan, br.branch_kabupaten, 
            br.branch_provinsi, br.branch_negara, br.branch_kode_pos, br.branch_phone1');
        $main_builder->join('m_cust as b', 'a.inv_invoice_cust_uuid = b.cust_uuid', 'left');
        $main_builder->join('m_cust_bill as bill', 'a.inv_invoice_cust_bill_uuid = bill.cust_bill_uuid', 'left');
        $main_builder->join('m_cust_bill_tax as tax', 'a.inv_invoice_cust_bill_tax_uuid = tax.bill_tax_uuid', 'left');
        $main_builder->join('mkt_contract as con', 'a.inv_invoice_contract_uuid = con.mkt_contract_uuid', 'left');
        $main_builder->join('mkt_quotation as q', 'con.mkt_contract_quotation_uuid = q.mkt_quotation_uuid', 'left');
        $main_builder->join('m_branch as br', 'br.branch_uuid = con.mkt_contract_branch_uuid','left');
        $main_builder->join('m_building as bldg', 'FIND_IN_SET(bldg.building_uuid, q.mkt_quotation_building_uuid)', 'left');
        $main_builder->where('a.inv_invoice_uuid', $invoice_uuid);

        $data['invoice'] = $main_builder->get()->getRowArray();

        if ($data['invoice']) {
            // 2. Get all Job Services linked to this invoice
            $services_in_invoice = $this->db->table('inv_invoice_item as a')
                                    ->join('opr_service as b', 'a.inv_invoice_item_service_uuid = b.opr_service_uuid')
                                    ->join('opr_schedule as c', 'b.opr_service_schedule_uuid = c.opr_schedule_uuid')
                                    ->where('a.inv_invoice_item_invoice_uuid', $invoice_uuid)
                                    ->select('b.opr_service_no, b.opr_service_date, c.opr_schedule_period_run, c.opr_schedule_uuid, c.opr_schedule_contract_uuid')
                                    ->get()->getResultArray();
            
            $invoice_details = [];
            foreach ($services_in_invoice as $service) {
                $service_entry = [
                    'service_no' => $service['opr_service_no'],
                    'service_date' => $service['opr_service_date'],
                    'period' => $service['opr_schedule_period_run'],
                    'mr_no' => 'N/A',
                    'items' => [] // This will hold ALL items (assets and refills)
                ];

                // --- REWORKED LOGIC STARTS HERE ---

                // A. Get all 'Asset' (Sewa) items directly from the quotation
                $asset_builder = $this->db->table('mkt_quotation_order as a');
                $asset_builder->select('a.mkt_quotation_order_item_qty AS wr_matrequest_item_item_qty, b.inventory_name, b.inventory_jenis, d.room_name, e.building_name');
                $asset_builder->join('m_inventory as b', 'a.mkt_quotation_order_unit_inventory_uuid = b.inventory_uuid');
                $asset_builder->join('m_room as d', 'a.mkt_quotation_order_room_uuid = d.room_uuid', 'left');
                $asset_builder->join('m_building as e', 'a.mkt_quotation_order_building_uuid = e.building_uuid', 'left');
                $asset_builder->where('a.mkt_quotation_order_quotation_uuid', $data['invoice']['mkt_contract_quotation_uuid']);
                $asset_builder->where('b.inventory_jenis', 'Asset');
                $asset_items = $asset_builder->get()->getResultArray();

                // B. Get 'Retail' (Refill) items from the period's specific Material Request
                $retail_items = [];
                $material_request = $this->db->table('wr_matrequest')
                                    ->where('wr_matrequest_opr_schedule_uuid', $service['opr_schedule_uuid'])
                                    ->get()->getRowArray();

                if ($material_request) {
                    $service_entry['mr_no'] = $material_request['wr_matrequest_no'];
                    
                    $retail_builder = $this->db->table('wr_matrequest_item as a');
                    $retail_builder->select('a.wr_matrequest_item_item_qty, b.inventory_name, b.inventory_jenis, "N/A" as room_name, "N/A" as building_name'); // No location for refills
                    $retail_builder->join('m_inventory as b', 'a.wr_matrequest_item_inventory_uuid = b.inventory_uuid');
                    $retail_builder->where('a.wr_matrequest_item_matrequest_uuid', $material_request['wr_matrequest_uuid']);
                    $retail_builder->where('b.inventory_jenis !=', 'Asset'); // Get everything that is NOT an asset
                    $retail_items = $retail_builder->get()->getResultArray();
                }
                
                // C. Combine both Asset and Retail items
                $service_entry['items'] = array_merge($asset_items, $retail_items);
                
                // --- REWORKED LOGIC ENDS HERE ---
                
                $invoice_details[] = $service_entry;
            }
            
            $data['invoice_details'] = $invoice_details;
        }

        return $data;
    }
}