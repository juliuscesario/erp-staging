<?php
namespace Modules\Warehouse\Models;
use CodeIgniter\Model;

class MatrequestItemModel extends Model
{
    protected $table         = 'wr_matrequest_item';
    protected $primaryKey    = 'wr_matrequest_item_id';
    protected $allowedFields = [
        'wr_matrequest_item_matrequest_uuid', 'wr_matrequest_item_id',
        'wr_matrequest_item_inventory_uuid', 
        'wr_matrequest_item_batch_uuid', // Kolom ini akan menyimpan JSON lokasi & link QR
        'wr_matrequest_item_item_qty', 'wr_matrequest_item_created_by'
    ];
}