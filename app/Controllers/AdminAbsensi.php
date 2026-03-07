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
                        WHEN EXISTS (
                            SELECT 1
                            FROM absensi_detail d2
                            JOIN absensi a2 ON a2.id = d2.absensi_id
                            WHERE d2.status != 'batal'
                              AND d2.murid_id = ad.murid_id
                              AND d2.tanggal = a.tanggal
                              AND COALESCE(a2.jenis_presensi, 'reguler') = COALESCE(a.jenis_presensi, 'reguler')
                            GROUP BY d2.murid_id, d2.tanggal, COALESCE(a2.jenis_presensi, 'reguler')
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
                COALESCE(a.jenis_presensi, "reguler") AS jenis_presensi,
                a.keterangan,
                a.lokasi_id,
                COALESCE(NULLIF(a.keterangan, ""), NULLIF(a.lokasi_text, ""), li.nama_lokasi) AS nama_lokasi,
                u.nama_depan AS guru_depan,
                u.nama_belakang AS guru_belakang,
                COUNT(ad.murid_id) OVER (PARTITION BY ad.murid_id, COALESCE(a.jenis_presensi, "reguler")) AS dobel
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
            $r['nama_lokasi'] = formatLokasiDisplay($r['nama_lokasi'] ?? '-', $r['keterangan'] ?? null);
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
                COALESCE(a.jenis_presensi, "reguler") AS jenis_presensi,
                a.keterangan,
                a.lokasi_id,
                COALESCE(NULLIF(a.keterangan, ""), NULLIF(a.lokasi_text, ""), li.nama_lokasi) AS nama_lokasi,
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
            $d['nama_lokasi'] = formatLokasiDisplay($d['nama_lokasi'] ?? '-', $d['keterangan'] ?? null);
        }
        unset($d);

        if ($mode === 'pdf') {
            $html = view('admin/rekap_absensi_pdf', [
                'judul'   => 'REKAP ABSENSI HARIAN',
                'tanggal' => $tanggal,
                'start'   => $tanggal,
                'data'    => $data,
                'mode'    => $jenis
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

        $this->renderExcelRows($data, $jenis, $tanggal);
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
                        WHEN EXISTS (
                            SELECT 1
                            FROM absensi_detail d2
                            JOIN absensi a2 ON a2.id = d2.absensi_id
                            WHERE d2.status != 'batal'
                              AND d2.murid_id = ad.murid_id
                              AND d2.tanggal = a.tanggal
                              AND COALESCE(a2.jenis_presensi, 'reguler') = COALESCE(a.jenis_presensi, 'reguler')
                            GROUP BY d2.murid_id, d2.tanggal, COALESCE(a2.jenis_presensi, 'reguler')
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
                COALESCE(a.jenis_presensi, "reguler") AS jenis_presensi,
                a.keterangan,
                COALESCE(NULLIF(a.keterangan, ""), NULLIF(a.lokasi_text, ""), li.nama_lokasi) AS nama_lokasi,
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
            $r['nama_lokasi'] = formatLokasiDisplay($r['nama_lokasi'] ?? '-', $r['keterangan'] ?? null);
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

    private function renderExcelRows(array $rows, string $mode, ?string $tanggal = null): void
    {
        if ($mode === 'all') {
            echo "REGULER\n";
            $this->renderExcelRows(array_values(array_filter($rows, static fn ($row) => (($row['jenis_presensi'] ?? 'reguler') === 'reguler'))), 'reguler', $tanggal);
            echo "\nUNITY\n";
            $this->renderExcelRows(array_values(array_filter($rows, static fn ($row) => (($row['jenis_presensi'] ?? 'reguler') === 'unity'))), 'unity', $tanggal);
            return;
        }

        $guruLabel = $mode === 'unity' ? 'Mentor' : 'Guru';
        echo "Tanggal\tNama\tKelas\tJam\tLokasi\t{$guruLabel}\n";

        foreach ($rows as $row) {
            $rowTanggal = $tanggal ?? ($row['tanggal'] ?? '-');
            echo
                $rowTanggal."\t".
                ($row['display_nama'] ?? trim(($row['nama_depan'] ?? '').' '.($row['nama_belakang'] ?? '')))."\t".
                ($row['nama_kelas'] ?? $row['kelas_id'] ?? '-')."\t".
                ($row['jam'] ?? '-')."\t".
                ($row['nama_lokasi'] ?? '-')."\t".
                trim(($row['guru_depan'] ?? '').' '.($row['guru_belakang'] ?? ''))."\n";
        }
    }
}
