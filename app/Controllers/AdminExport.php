<?php

namespace App\Controllers;

class AdminExport extends BaseController
{
    protected $db;

    public function __construct(){
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function mingguan()
    {
        $end   = date('Y-m-d');
        $start = date('Y-m-d', strtotime('-6 days'));
        $this->exportRange($start, $end, 'mingguan');
    }

    public function bulanan()
    {
        $start = date('Y-m-01');
        $end   = date('Y-m-t');
        $this->exportRange($start, $end, 'bulanan');
    }

    public function tahunan()
    {
        $start = date('Y-01-01');
        $end   = date('Y-12-31');
        $this->exportRange($start, $end, 'tahunan');
    }

    private function exportRange($start, $end, $label)
    {
        $hasUnity = $this->hasTableColumn('murid', 'unity');
        $hasJenisPresensi = $this->hasTableColumn('absensi', 'jenis_presensi');
        $mode = strtolower(trim((string) $this->request->getGet('mode')));
        if (!in_array($mode, ['all', 'reguler', 'unity'], true)) {
            $mode = 'all';
        }
        $rows = $this->db->table('absensi_detail ad')
            ->select('
                a.tanggal,
                m.nama_depan,
                m.nama_belakang,
                m.panggilan,
                '.($hasUnity ? 'm.unity' : "'' AS unity").',
                k.nama_kelas,
                a.jam,
                li.nama_lokasi,
                u.nama_depan AS guru_depan,
                u.nama_belakang AS guru_belakang
            ')
            ->join('absensi a','a.id=ad.absensi_id')
            ->join('murid m','m.id=ad.murid_id')
            ->join('kelas k','k.id=m.kelas_id','left')
            ->join('lokasi_ibadah li','li.id=a.lokasi_id','left')
            ->join('users u','u.id=a.guru_id','left')
            ->where('ad.status','hadir')
            ->where('a.tanggal >=',$start)
            ->where('a.tanggal <=',$end);

        if ($hasJenisPresensi && $mode !== 'all') {
            $rows->where('a.jenis_presensi', $mode);
        }

        $rows = $rows
            ->orderBy('a.tanggal','ASC')
            ->get()->getResultArray();

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=absensi_$label.xls");

        echo "ABSENSI KEHADIRAN MURID\n";
        echo "DSCM KIDS\n";
        echo "Periode : $start s/d $end\n";
        echo "Jenis   : ".strtoupper($mode)."\n";
        echo "Dicetak : ".date('d M Y H:i')."\n\n";
        echo "Tanggal\tNama\tKelas\tUnity\tJam\tLokasi\tGuru\n";

        foreach ($rows as $r) {

            $namaLengkap = trim($r['nama_depan'].' '.$r['nama_belakang']);
            $displayNama = !empty($r['panggilan'])
                ? $r['panggilan'].' ('.$namaLengkap.')'
                : $namaLengkap;

            echo
                $r['tanggal']."\t".
                $displayNama."\t".
                ($r['nama_kelas'] ?? '-')."\t".
                ($r['unity'] ?? '-')."\t".
                ($r['jam'] ?? '-')."\t".
                ($r['nama_lokasi'] ?? '-')."\t".
                trim(($r['guru_depan'] ?? '').' '.($r['guru_belakang'] ?? ''))."\n";
        }
        exit;
    }
}
