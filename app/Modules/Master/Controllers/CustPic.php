<?php
namespace Modules\Master\Controllers;
use App\Controllers\BaseController;
use Ramsey\Uuid\Uuid;

class CustPic extends BaseController
{
    // Menampilkan form untuk menambah PIC baru
    public function create($cust_uuid)
    {
        $data['cust_uuid'] = $cust_uuid;
        return view('Modules\Master\Views\pic_create', $data);
    }

    // Menyimpan data PIC baru
    // app/Modules/Master/Controllers/CustPic.php
    public function store()
    {
        $picModel = new \Modules\Master\Models\CustPicModel();
        $uuid = Uuid::uuid4()->toString();

        $newData = [
            'cust_pic_uuid' => $uuid,
            'cust_pic_cust_uuid' => $this->request->getPost('cust_pic_cust_uuid'),
            'cust_pic_panggilan' => $this->request->getPost('cust_pic_panggilan'),
            'cust_pic_kontak' => $this->request->getPost('cust_pic_kontak'),
            'cust_pic_position' => $this->request->getPost('cust_pic_position'),
            'cust_pic_phone1' => $this->request->getPost('cust_pic_phone1'),
        ];
        
        if ($picModel->insert($newData)) {
            // Ambil kembali data yang baru disimpan untuk dikirim balik
            $newPicData = $picModel->find($uuid);
            return $this->response->setJSON(['status' => 'success', 'new_pic' => $newPicData]);
        } else {
            return $this->response->setJSON(['status' => 'error']);
        }
    }

    // Menampilkan form untuk mengedit PIC
    public function edit($pic_uuid)
    {
        $picModel = new \Modules\Master\Models\CustPicModel();
        $data['pic'] = $picModel->find($pic_uuid);
        
        return view('Modules\Master\Views\pic_edit', $data);
    }

    // Mengupdate data PIC yang diedit
    public function update($pic_uuid)
    {
        $picModel = new \Modules\Master\Models\CustPicModel();
        
        $picModel->update($pic_uuid, [
            'cust_pic_panggilan' => $this->request->getPost('cust_pic_panggilan'),
            'cust_pic_kontak' => $this->request->getPost('cust_pic_kontak'),
            'cust_pic_position' => $this->request->getPost('cust_pic_position'),
            'cust_pic_phone1' => $this->request->getPost('cust_pic_phone1'),
        ]);

        return redirect()->back()->with('success', 'Data PIC berhasil diperbarui.');
    }
}