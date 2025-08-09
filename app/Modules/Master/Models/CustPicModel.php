<?php
namespace Modules\Master\Models;
use CodeIgniter\Model;

class CustPicModel extends Model
{
    protected $table         = 'm_cust_pic';
    protected $primaryKey    = 'cust_pic_uuid';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $allowedFields = [
        'cust_pic_uuid',
        'cust_pic_id',
        'cust_pic_cust_uuid',
        'cust_pic_panggilan',
        'cust_pic_kontak',
        'cust_pic_phone1',
        'cust_pic_phone2',
        'cust_pic_position',
        'cust_pic_email',
        'cust_pic_aktif',
        'cust_pic_created_by'
    ];
}