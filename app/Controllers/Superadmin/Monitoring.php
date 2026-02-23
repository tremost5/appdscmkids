<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use Config\Database;

class Monitoring extends BaseController
{
    protected $db;

    public function __construct(){
        parent::__construct();
        $this->db = Database::connect();
        helper('tahun_ajaran');
    }

    public function index()
    {
        $tahunAjaranId = tahunAjaranIdAktif();
        $warning = null;
        if (!$tahunAjaranId) {
            $warning = 'Tahun ajaran aktif tidak ditemukan. Total absensi guru ditampilkan sebagai 0.';
        }


        // ADMIN (role 2)
        $admin = $this->db->table('users u')
            ->select('
                u.id,
                u.nama_depan,
                u.nama_belakang,
                u.last_seen,
                u.is_active
            ')
            ->where('u.role_id', 2)
            ->orderBy('u.nama_depan', 'ASC')
            ->get()
            ->getResultArray();

        // GURU (role 3) + statistik absensi
        $guruBuilder = $this->db->table('users u')
            ->where('u.role_id', 3)
            ->orderBy('u.nama_depan', 'ASC');

        if ($tahunAjaranId) {
            $guruBuilder
                ->select('
                    u.id,
                    u.nama_depan,
                    u.nama_belakang,
                    u.last_seen,
                    COUNT(a.id) AS total_absen
                ')
                ->join(
                    'absensi a',
                    'a.guru_id = u.id AND a.tahun_ajaran_id = ' . (int) $tahunAjaranId,
                    'left'
                )
                ->groupBy(['u.id', 'u.nama_depan', 'u.nama_belakang', 'u.last_seen']);
        } else {
            $guruBuilder->select('
                u.id,
                u.nama_depan,
                u.nama_belakang,
                u.last_seen,
                0 AS total_absen
            ');
        }

        $guru = $guruBuilder->get()->getResultArray();

        foreach ($guru as &$g) {
            $lastSeen = $g['last_seen'] ?? null;
            $g['online'] = $lastSeen && (time() - strtotime($lastSeen) <= 300);
        }
        unset($g);


        return view('superadmin/monitoring/index', [
            'admin' => $admin,
            'guru'  => $guru,
            'warning' => $warning,
        ]);
    }
}
