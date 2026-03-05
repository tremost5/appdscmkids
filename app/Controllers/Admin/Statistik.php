<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StatistikModel;

class Statistik extends BaseController
{
    protected $statistik;

    public function __construct(){
        parent::__construct();
        $this->statistik = new StatistikModel();
    }

    public function index()
    {
        $mode = strtolower(trim((string) $this->request->getGet('mode')));
        if (!in_array($mode, ['all', 'reguler', 'unity'], true)) {
            $mode = 'all';
        }

        $data = [
            'title'           => 'Statistik Kehadiran',
            'total_murid'     => $this->statistik->totalMurid(),
            'hadir_hari_ini'  => $this->statistik->hadirHariIni($mode),
            'absen_bulan_ini' => $this->statistik->absenPerBulan($mode),
            'mode'            => $mode,
        ];

        return view('admin/statistik', $data);
    }
}
