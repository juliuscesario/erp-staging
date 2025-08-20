<?php
namespace Modules\Finance\Models;
use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table         = 'fin_payment';
    protected $primaryKey    = 'payment_uuid';
    protected $allowedFields = [
        'payment_uuid', 'payment_id', 'payment_no', 'payment_date', 
        'payment_invoice_uuid', 'payment_method', 'payment_amount', 
        'payment_reference', 'payment_created_by'
    ];
}