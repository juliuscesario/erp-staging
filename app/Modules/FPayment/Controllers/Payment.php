<?php
namespace Modules\FPayment\Controllers;
use App\Controllers\BaseController;
use Ramsey\Uuid\Uuid;
use Modules\FPayment\Models\PaymentModel;

class Payment extends BaseController
{
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        // Memuat helper di constructor agar tersedia di semua method
        helper('jurnal'); 
    }

    public function index()
    {
        $builder = $this->db->table('inv_invoice as a');
        $builder->select('a.*, b.cust_name');
        $builder->join('m_cust as b', 'a.inv_invoice_cust_uuid = b.cust_uuid', 'left');
        $builder->orderBy('a.inv_invoice_date', 'DESC');
        $data['invoices'] = $builder->get()->getResultArray();

        return view('Modules\FPayment\Views\index', $data);
    }

    // Method untuk menampilkan form pembayaran via AJAX
    public function create($invoice_uuid)
    {
        // Query untuk mengambil semua data invoice yang relevan
        $data['invoice'] = $this->db->table('inv_invoice')->where('inv_invoice_uuid', $invoice_uuid)->get()->getRowArray();
        
        if (empty($data['invoice'])) {
            return '<p class="text-danger">Invoice tidak ditemukan.</p>';
        }

        return view('Modules\FPayment\Views\create', $data);
    }

    // Method untuk menyimpan data pembayaran
    public function store()
    {
        // 1. Validasi Input
        $validation = $this->validate([
            'invoice_uuid'   => 'required',
            'payment_date'   => 'required|valid_date',
            'payment_method' => 'required',
            'payment_type'   => 'required',
            'payment_amount' => 'required|numeric'
        ]);

        if (!$validation) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }
        
        $paymentModel = new PaymentModel();
        $invoiceUUID = $this->request->getPost('invoice_uuid');
        
        // Ambil semua data invoice untuk dikirim ke helper
        $invoice = $this->db->table('inv_invoice as a')
            ->select('a.*, b.cust_name') 
            ->join('m_cust as b', 'a.inv_invoice_cust_uuid = b.cust_uuid', 'left')
            ->where('a.inv_invoice_uuid', $invoiceUUID)
            ->get()->getRow();

        if (!$invoice) {
            return redirect()->back()->with('error', 'Invoice tidak ditemukan.');
        }

        // 2. Gunakan Database Transaction
        $this->db->transStart();

        // 3. Dapatkan ID terakhir dan buat nomor Payment
        $lastIdRow = $paymentModel->selectMax('payment_id', 'last_id')->get()->getRow();
        $new_id = ($lastIdRow && $lastIdRow->last_id ? $lastIdRow->last_id : 0) + 1;
        $new_no = sprintf('PAY-ART/%s-%s/%04d', date('m'), date('y'), $new_id);
        $paymentUUID = Uuid::uuid4()->toString();
        
        // Kumpulkan semua data pembayaran ke dalam satu array
        $paymentData = [
            'payment_uuid'        => $paymentUUID,
            'payment_id'          => $new_id,
            'payment_no'          => $new_no,
            'payment_invoice_uuid'=> $invoiceUUID,
            'payment_date'        => $this->request->getPost('payment_date'),
            'payment_method'      => $this->request->getPost('payment_method'),
            'payment_type'        => $this->request->getPost('payment_type'),
            'payment_amount'      => $this->request->getPost('payment_amount'),
            'payment_reference'   => $this->request->getPost('payment_reference'),
        ];

        $paymentModel->insert($paymentData);

        // 4. Update status invoice menjadi "Paid"
        $this->db->table('inv_invoice')->where('inv_invoice_uuid', $invoiceUUID)->update(['inv_invoice_status' => 'Paid']);

        // 5. Panggil helper jurnal dengan mengirim objek invoice dan array payment
        catatJurnalPembayaran($invoice, $paymentData);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            // Jika transaksi gagal, catat error dan beri notifikasi
            log_message('error', 'Gagal menyimpan pembayaran: ' . print_r($this->db->error(), true));
            return redirect()->back()->with('error', 'Terjadi kesalahan pada database, pembayaran gagal disimpan.');
        }

        return redirect()->to(site_url('fpayment/payment'))->with('success', 'Pembayaran berhasil disimpan dan dijurnal.');
    }
}