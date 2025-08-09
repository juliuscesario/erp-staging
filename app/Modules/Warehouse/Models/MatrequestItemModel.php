<?php
namespace Modules\Warehouse\Models;
use CodeIgniter\Model;

class MatrequestItemModel extends Model
{
    protected $table         = 'wr_matrequest_item';
    protected $primaryKey    = 'wr_matrequest_item_id';
    protected $allowedFields = [
        'wr_matrequest_item_matrequest_uuid', 'wr_matrequest_item_id',
        'wr_matrequest_item_inventory_uuid', 'wr_matrequest_item_qrcode_link','wr_matrequest_item_qrcode_image',
        'wr_matrequest_item_item_qty', 'wr_matrequest_item_created_by'
    ];
}