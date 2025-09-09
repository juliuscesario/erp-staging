<?php
namespace Modules\Warehouse\Models;
use CodeIgniter\Model;

class MatrequestModel extends Model
{
    protected $table         = 'wr_matrequest';
    protected $primaryKey    = 'wr_matrequest_uuid';
    protected $allowedFields = [
        'wr_matrequest_uuid', 'wr_matrequest_id',
        'wr_matrequest_opr_schedule_uuid', 'wr_matrequest_no', 'wr_matrequest_date',
        'wr_matrequest_kar_uuid_sign1', 'wr_matrequest_sign1',
        'wr_matrequest_kar_uuid_sign2', 'wr_matrequest_sign2', 'wr_matrequest_status',
        'wr_matrequest_created_by'
    ];
}