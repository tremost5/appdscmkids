<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Dompdf\Dompdf;

class AdminAbsensi extends BaseController
{
    protected $db;

    public function __construct(){
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /* =====================================================
     * LANDING REKAP ABSENSI
     * ===================================================== */
    public function index()
    {
        return $this->range();
    }

    /* =====================================================
     * REKAP ABSENSI RENTANG TANGGAL (PER TANGGAL)
     * ===================================================== */
    public function range()
    {
        $hasUnity = $this->hasTableColumn('murid', 'unity');
        $hasJenisPresensi = $this->hasTableColumn('absensi', 'jenis_presensi');
        $start = $this->request->getGet('start');
        $end   = $this->request->getGet('end');
        $kelas = $this->request->getGet('kelas');
        $unity = trim((string) $this->request->getGet('unity'));
        $mode  = $this->resolvePresensiMode($this->request->getGet('mode'));

        if (!$start || !$end) {
            return view('admin/rekap_absensi_range', [
                'rows'  => [],
                'start' => $start,
                'end'   => $end,
                'kelas' => $kelas,
                'unity' => $unity,
                'mode'  => $mode
            ]);
        }

        $builder = $this->db->table('absensi_detail ad')
            ->select("
                a.tanggal,
                COUNT(DISTINCT ad.murid_id) AS total_hadir,
                COUNT(DISTINCT m.kelas_id) AS total_kelas,
                COUNT(DISTINCT a.guru_id) AS total_guru,
                SUM(
                    CASE 
                        WHEN ad.murid_id IN (
                            SELECT murid_id
                            FROM absensi_detail
                            WHERE status != 'batal'
                            GROUP BY murid_id, tanggal
                            HAVING COUNT(*) > 1
                        )
                        THEN 1 ELSE 0
                    END
                ) AS has_dobel
            ")
            ->join('absensi a', 'a.id = ad.absensi_id')
            ->join('murid m', 'm.id = ad.murid_id')
            ->where('ad.status', 'hadir')
            ->where('a.tanggal', '>=', $start)
            ->where('a.tanggal', '<=', $end);

        if ($kelas) {
            $builder->where('m.kelas_id', $kelas);
        }
        if ($hasUnity && $unity !== '') {
            $builder->where('m.unity', $unity);
        }
        if ($hasJenisPresensi && $mode !== 'all') {
            $builder->where('a.jenis_presensi', $mode);
        }

        $rows = $builder
            ->groupBy('a.tanggal')
            ->orderBy('a.tanggal', 'DESC')
            ->get()
            ->getResultArray();

        return view('admin/rekap_absensi_range', [
            'rows'  => $rows,
            'start' => $start,
            'end'   => $end,
            'kelas' => $kelas,
            'unity' => $unity,
            'mode'  => $mode
        ]);
    }

    /* =====================================================
     * DETAIL ABSENSI PER TANGGAL
     * ===================================================== */
    public function detailTanggal($tanggal)
    {
        $hasUnity = $this->hasTableColumn('murid', 'unity');
        $hasJenisPresensi = $this->hasTableColumn('absensi', 'jenis_presensi');
        $kelas  = $this->request->getGet('kelas');
        $guru   = $this->request->getGet('guru');
        $lokasi = $this->request->getGet('lokasi');
        $unity  = trim((string) $this->request->getGet('unity'));
        $mode   = $this->resolvePresensiMode($this->request->getGet('mode'));

        $guruList = $this->db->table('users')
            ->select('id, nama_depan, nama_belakang')
            ->where('role_id', 3)
            ->orderBy('nama_depan', 'ASC')
            ->get()
            ->getResultArray();

        $builder = $this->db->table('absensi_detail ad')
            ->select('
                ad.murid_id,
                m.nama_depan,
                m.nama_belakang,
                m.panggilan,
                m.kelas_id,
                '.($hasUnity ? 'm.unity' : "'' AS unity").',
                a.jam,
                a.lokasi_id,
                li.nama_lokasi,
                u.nama_depan AS guru_depan,
                u.nama_belakang AS guru_belakang,
                COUNT(ad.murid_id) OVER (PARTITION BY ad.murid_id) AS dobel
            ')
            ->join('absensi a', 'a.id = ad.absensi_id')
            ->join('murid m', 'm.id = ad.murid_id')
            ->join('lokasi_ibadah li', 'li.id = a.lokasi_id', 'left')
            ->join('users u', 'u.id = a.guru_id', 'left')
            ->where('a.tanggal', $tanggal)
            ->where('ad.status', 'hadir');

        if ($kelas) {
            $builder->where('m.kelas_id', $kelas);
        }

        if ($guru) {
            $builder->where('a.guru_id', $guru);
        }

        if ($lokasi) {
            $builder->where('a.lokasi_id', $lokasi);
        }
        if ($hasUnity && $unity !== '') {
            $builder->where('m.unity', $unity);
        }
        if ($hasJenisPresensi && $mode !== 'all') {
            $builder->where('a.jenis_presensi', $mode);
        }

        $rows = $builder
            ->orderBy('m.kelas_id', 'ASC')
            ->orderBy('m.nama_depan', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as &$r) {
            $namaLengkap = trim($r['nama_depan'].' '.$r['nama_belakang']);
            $r['display_nama'] = !empty($r['panggilan'])
                ? $r['panggilan'].' ('.$namaLengkap.')'
                : $namaLengkap;
        }
        unset($r);

        $summary = [
            'total_hadir' => count($rows),
            'total_kelas' => count(array_unique(array_column($rows, 'kelas_id'))),
            'total_dobel' => count(array_filter($rows, fn($r) => $r['dobel'] > 1))
        ];

        return view('admin/rekap_absensi_detail', [
            'tanggal'  => $tanggal,
            'rows'     => $rows,
            'summary'  => $summary,
            'kelas'    => $kelas,
            'guru'     => $guru,
            'lokasi'   => $lokasi,
            'unity'    => $unity,
            'mode'     => $mode,
            'guruList' => $guruList
        ]);
    }

    /* =====================================================
     * EXPORT DETAIL (PDF / EXCEL)
     * ===================================================== */
    public function export($mode, $tanggal)
    {
        $hasUnity = $this->hasTableColumn('murid', 'unity');
        $hasJenisPresensi = $this->hasTableColumn('absensi', 'jenis_presensi');
        $unity = trim((string) $this->request->getGet('unity'));
        $jenis = $this->resolvePresensiMode($this->request->getGet('mode'));

        $data = $this->db->table('absensi_detail ad')
            ->select('
                m.nama_depan,
                m.nama_belakang,
                m.panggilan,
                m.kelas_id,
                '.($hasUnity ? 'm.unity' : "'' AS unity").',
                k.nama_kelas,
                a.jam,
                a.lokasi_id,
                li.nama_lokasi,
                u.nama_depan AS guru_depan,
                u.nama_belakang AS guru_belakang
            ')
            ->join('absensi a', 'a.id = ad.absensi_id')
            ->join('murid m', 'm.id = ad.murid_id')
            ->join('kelas k', 'k.id = m.kelas_id', 'left')
            ->join('lokasi_ibadah li', 'li.id = a.lokasi_id', 'left')
            ->join('users u', 'u.id = a.guru_id', 'left')
            ->where('ad.status', 'hadir')
            ->where('a.tanggal', $tanggal);

        if ($hasUnity && $unity !== '') {
            $data->where('m.unity', $unity);
        }
        if ($hasJenisPresensi && $jenis !== 'all') {
            $data->where('a.jenis_presensi', $jenis);
        }

        $data = $data
            ->orderBy('m.kelas_id', 'ASC')
            ->orderBy('m.nama_depan', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($data as &$d) {
            $namaLengkap = trim($d['nama_depan'].' '.$d['nama_belakang']);
            $d['display_nama'] = !empty($d['panggilan'])
                ? $d['panggilan'].' ('.$namaLengkap.')'
                : $namaLengkap;
        }
        unset($d);

        if ($mode === 'pdf') {
            $html = view('admin/rekap_absensi_pdf', [
                'judul'   => 'REKAP ABSENSI HARIAN',
                'tanggal' => $tanggal,
                'start'   => $tanggal,
                'data'    => $data
            ]);

            $dompdf = new Dompdf(['isRemoteEnabled' => true]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream("rekap_$tanggal.pdf", ['Attachment' => true]);
            exit;
        }

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=rekap_$tanggal.xls");

        echo "Nama\tKelas\tUnity\tLokasi\tJam\tGuru\n";
        foreach ($data as $d) {
            echo
                $d['display_nama']."\t".
                ($d['nama_kelas'] ?? '-')."\t".
                ($d['unity'] ?? '-')."\t".
                ($d['nama_lokasi'] ?? '-')."\t".
                ($d['jam'] ?? '-')."\t".
                trim(($d['guru_depan'] ?? '').' '.($d['guru_belakang'] ?? ''))."\n";
        }
        exit;
    }

    /* =====================================================
     * REKAP ABSENSI PER KELAS
     * ===================================================== */
    public function kelas()
    {
        $hasUnity = $this->hasTableColumn('murid', 'unity');
        $hasJenisPresensi = $this->hasTableColumn('absensi', 'jenis_presensi');
        $start = $this->request->getGet('start');
        $end   = $this->request->getGet('end');
        $kelas = $this->request->getGet('kelas');
        $unity = trim((string) $this->request->getGet('unity'));
        $mode  = $this->resolvePresensiMode($this->request->getGet('mode'));

        if (!$start || !$end) {
            return view('admin/rekap_absensi_kelas', [
                'rows'  => [],
                'start' => $start,
                'end'   => $end,
                'kelas' => $kelas,
                'unity' => $unity,
                'mode'  => $mode
            ]);
        }

        $builder = $this->db->table('absensi_detail ad')
            ->select("
                m.kelas_id,
                k.nama_kelas,
                COUNT(DISTINCT ad.murid_id) AS total_hadir,
                COUNT(DISTINCT a.tanggal) AS total_hari,
                COUNT(DISTINCT a.guru_id) AS total_guru,
                SUM(
                    CASE 
                        WHEN ad.murid_id IN (
                            SELECT murid_id
                            FROM absensi_detail
                            WHERE status != 'batal'
                            GROUP BY murid_id, tanggal
                            HAVING COUNT(*) > 1
                        )
                        THEN 1 ELSE 0
                    END
                ) AS has_dobel
            ")
            ->join('absensi a', 'a.id = ad.absensi_id')
            ->join('murid m', 'm.id = ad.murid_id')
            ->join('kelas k', 'k.id = m.kelas_id', 'left')
            ->where('ad.status', 'hadir')
            ->where('a.tanggal', '>=', $start)
            ->where('a.tanggal', '<=', $end);

        if ($kelas) {
            $builder->where('m.kelas_id', $kelas);
        }
        if ($hasUnity && $unity !== '') {
            $builder->where('m.unity', $unity);
        }
        if ($hasJenisPresensi && $mode !== 'all') {
            $builder->where('a.jenis_presensi', $mode);
        }

        $rows = $builder
            ->groupBy('m.kelas_id')
            ->orderBy('k.nama_kelas', 'ASC')
            ->get()
            ->getResultArray();

        return view('admin/rekap_absensi_kelas', [
            'rows'  => $rows,
            'start' => $start,
            'end'   => $end,
            'kelas' => $kelas,
            'unity' => $unity,
            'mode'  => $mode
        ]);
    }

    /* =====================================================
     * DETAIL ABSENSI PER KELAS
     * ===================================================== */
    public function kelasDetail()
    {
        $hasUnity = $this->hasTableColumn('murid', 'unity');
        $hasJenisPresensi = $this->hasTableColumn('absensi', 'jenis_presensi');
        $kelas = $this->request->getGet('kelas');
        $start = $this->request->getGet('start');
        $end   = $this->request->getGet('end');
        $unity = trim((string) $this->request->getGet('unity'));
        $mode  = $this->resolvePresensiMode($this->request->getGet('mode'));

        if (!$kelas || !$start || !$end) {
            return redirect()->to('dashboard/admin/rekap-absensi/kelas');
        }

        $guruList = $this->db->table('users')
            ->select('id, nama_depan, nama_belakang')
            ->where('role_id', 3)
            ->orderBy('nama_depan','ASC')
            ->get()->getResultArray();

        $guru   = $this->request->getGet('guru');
        $lokasi = $this->request->getGet('lokasi');

        $builder = $this->db->table('absensi_detail ad')
            ->select('
                a.tanggal,
                m.nama_depan,
                m.nama_belakang,
                m.panggilan,
                '.($hasUnity ? 'm.unity' : "'' AS unity").',
                a.jam,
                li.nama_lokasi,
                u.nama_depan AS guru_depan,
                u.nama_belakang AS guru_belakang
            ')
            ->join('absensi a', 'a.id = ad.absensi_id')
            ->join('murid m', 'm.id = ad.murid_id')
            ->join('users u', 'u.id = a.guru_id', 'left')
            ->join('lokasi_ibadah li', 'li.id = a.lokasi_id', 'left')
            ->where('m.kelas_id', $kelas)
            ->where('a.tanggal', '>=', $start)
            ->where('a.tanggal', '<=', $end);

        if ($guru) {
            $builder->where('a.guru_id', $guru);
        }

        if ($lokasi) {
            $builder->where('a.lokasi_id', $lokasi);
        }
        if ($hasUnity && $unity !== '') {
            $builder->where('m.unity', $unity);
        }
        if ($hasJenisPresensi && $mode !== 'all') {
            $builder->where('a.jenis_presensi', $mode);
        }

        $query = $builder
            ->orderBy('a.tanggal', 'DESC')
            ->orderBy('m.nama_depan', 'ASC')
            ->get();

        if ($query === false) {
            dd($this->db->error());
        }

        $rows = $query->getResultArray();

        foreach ($rows as &$r) {
            $namaLengkap = trim($r['nama_depan'].' '.$r['nama_belakang']);
            $r['display_nama'] = !empty($r['panggilan'])
                ? $r['panggilan'].' ('.$namaLengkap.')'
                : $namaLengkap;
        }
        unset($r);

        return view('admin/rekap_absensi_kelas_detail', [
            'rows'     => $rows,
            'kelas'    => $kelas,
            'start'    => $start,
            'end'      => $end,
            'guru'     => $guru,
            'lokasi'   => $lokasi,
            'unity'    => $unity,
            'mode'     => $mode,
            'guruList' => $guruList
        ]);
    }

    private function resolvePresensiMode($mode): string
    {
        $value = strtolower(trim((string) $mode));
        if (!in_array($value, ['all', 'reguler', 'unity'], true)) {
            return 'all';
        }

        return $value;
    }
}
