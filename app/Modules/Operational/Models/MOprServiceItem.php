<?php
namespace Modules\Operational\Models;
use CodeIgniter\Model;

class MOprServiceItem extends Model
{
    protected $table         = 'opr_service_item';
    protected $primaryKey    = 'opr_service_item_uuid';
    protected $allowedFields = [
        'opr_service_item_uuid',
        'opr_service_item_id',
        'opr_service_item_service_uuid',
        'opr_service_matrequest_uuid',         // <-- Tambahan baru
        'opr_service_item_inventory_uuid',
        'opr_service_item_building_uuid',      // <-- Tambahan baru
        'opr_service_item_room_uuid',          // <-- Tambahan baru
        'opr_service_item_problem',
        'opr_service_item_action',
        'opr_service_item_operation',
        'opr_service_item_image',
        'opr_service_item_status',
    ];
}