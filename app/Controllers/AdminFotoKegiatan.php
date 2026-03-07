<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class AdminFotoKegiatan extends BaseController
{
    protected $db;

    protected array $kelasOrder = [
        'PG','TKA','TKB','1','2','3','4','5','6','LULUS'
    ];

    public function __construct(){
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $tanggal = $this->request->getGet('tanggal') ?? date('Y-m-d');
        $kelas   = $this->request->getGet('kelas');

        // ===== LIST KELAS
        $kelasList = $this->db->table('kelas')
            ->select('id, kode_kelas')
            ->get()
            ->getResultArray();

        usort($kelasList, function ($a, $b) {
            return array_search($a['kode_kelas'], $this->kelasOrder)
                <=> array_search($b['kode_kelas'], $this->kelasOrder);
        });

        // ===== QUERY FOTO KEGIATAN (STRUKTUR BENAR)
        $builder = $this->db->table('absensi a')
            ->select('
                a.id,
                a.tanggal,
                a.jam,
                a.selfie_foto,
                MAX(a.keterangan) AS keterangan,
                MIN(k.kode_kelas) AS kode_kelas,
                MIN(COALESCE(NULLIF(a.keterangan, ""), NULLIF(a.lokasi_text, ""), li.nama_lokasi)) AS nama_lokasi,
                MIN(u.nama_depan) AS nama_depan,
                MIN(u.nama_belakang) AS nama_belakang
            ')
            ->join('absensi_detail ad', 'ad.absensi_id = a.id')
            ->join('murid m', 'm.id = ad.murid_id')
            ->join('kelas k', 'k.id = m.kelas_id')
            ->join('lokasi_ibadah li', 'li.id = a.lokasi_id', 'left')
            ->join('users u', 'u.id = a.guru_id', 'left')
            ->where('a.selfie_foto IS NOT NULL', null, false)
            ->where('a.tanggal', $tanggal)
            ->groupBy(['a.id', 'a.tanggal', 'a.jam', 'a.selfie_foto']);

        if ($kelas) {
            $builder->where('m.kelas_id', $kelas);
        }

        $rows = $builder
            ->orderBy('kode_kelas', 'ASC')
            ->orderBy('a.jam', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as &$row) {
            $row['nama_lokasi'] = formatLokasiDisplay($row['nama_lokasi'] ?? '-', $row['keterangan'] ?? null);
        }
        unset($row);

        usort($rows, function ($a, $b) {
            return array_search($a['kode_kelas'], $this->kelasOrder)
                <=> array_search($b['kode_kelas'], $this->kelasOrder);
        });

        return view('admin/foto_kegiatan', [
            'rows'      => $rows,
            'tanggal'   => $tanggal,
            'kelas'     => $kelas,
            'kelasList' => $kelasList
        ]);
    }
}

