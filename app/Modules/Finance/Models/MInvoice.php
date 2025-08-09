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
        $main_builder->select('a.*, b.cust_name, con.mkt_contract_no, con.mkt_contract_quotation_uuid');
        $main_builder->join('m_cust as b', 'a.inv_invoice_cust_uuid = b.cust_uuid', 'left');
        $main_builder->join('mkt_contract as con', 'a.inv_invoice_contract_uuid = con.mkt_contract_uuid', 'left');
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

                // 3. Get ALL items (Asset and Retail) from the Material Request of the CURRENT period
                $current_mr = $this->db->table('wr_matrequest')
                                    ->where('wr_matrequest_opr_schedule_uuid', $service['opr_schedule_uuid'])
                                    ->get()->getRowArray();

                if ($current_mr) {
                    $service_entry['mr_no'] = $current_mr['wr_matrequest_no'];
                    
                    $items_builder = $this->db->table('wr_matrequest_item as a');
                    $items_builder->select('a.wr_matrequest_item_item_qty, b.inventory_name, b.inventory_jenis, d.room_name, e.building_name');
                    $items_builder->join('m_inventory as b', 'a.wr_matrequest_item_inventory_uuid = b.inventory_uuid');
                    $items_builder->join('mkt_quotation_order as c', 'a.wr_matrequest_item_inventory_uuid = c.mkt_quotation_order_unit_inventory_uuid OR a.wr_matrequest_item_inventory_uuid = c.mkt_quotation_order_oil_inventory_uuid', 'left');
                    $items_builder->join('m_room as d', 'c.mkt_quotation_order_room_uuid = d.room_uuid', 'left');
                    $items_builder->join('m_building as e', 'c.mkt_quotation_order_building_uuid = e.building_uuid', 'left');
                    $items_builder->where('a.wr_matrequest_item_matrequest_uuid', $current_mr['wr_matrequest_uuid']);
                    $items_builder->where('c.mkt_quotation_order_quotation_uuid', $data['invoice']['mkt_contract_quotation_uuid']);
                    $items_builder->groupBy('a.wr_matrequest_item_inventory_uuid'); // Group to avoid duplicates
                    
                    $service_entry['items'] = $items_builder->get()->getResultArray();
                }
                
                $invoice_details[] = $service_entry;
            }
            
            $data['invoice_details'] = $invoice_details;
        }

        return $data;
    }
}