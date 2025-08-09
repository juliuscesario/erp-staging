<?php
namespace Modules\Dashboard\Controllers;
use App\Controllers\BaseController;

class Home extends BaseController
{
    public function index()
    {
        // Controller ini hanya bertugas menampilkan halaman dashboard
        return view('Modules\Dashboard\Views\index');
    }
}