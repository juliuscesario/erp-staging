<?php
namespace Modules\Jurnal\Controllers;
use App\Controllers\BaseController;
use Modules\Jurnal\Models\JurnalModel;

class Jurnal extends BaseController
{
    public function index()
    {
        return view('Modules\Jurnal\Views\index');
    }

    public function load_data()
    {
        $request = \Config\Services::request();
        $jurnalModel = new JurnalModel();

        $start = $request->getPost('start');
        $length = $request->getPost('length');
        $searchValue = $request->getPost('search')['value'];
        $order = $request->getPost('order');

        $list = $jurnalModel->getDataTable($start, $length, $searchValue, $order);
        $recordsTotal = $jurnalModel->countAllData();
        $recordsFiltered = $jurnalModel->countFilteredData($searchValue);

        $data = [
            "draw" => intval($request->getPost('draw')),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $list,
        ];

        return $this->response->setJSON($data);
    }
}