<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Dompdf\Dompdf;

class AdminNaikKelas extends BaseController
{
    protected $db;

    /**
     * URUTAN RESMI KELAS
     */
    protected array $kelasOrder = [
        'PG', 'TKA', 'TKB', '1', '2', '3', '4', '5', '6', 'LULUS'
    ];

    public function __construct(){
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /* =========================
     * CEK LOCK
     * ========================= */
    private function isLocked(): bool
    {
        $tahun = date('Y') . '/' . (date('Y') + 1);

        return $this->db->table('kelas_history')
            ->where('tahun_ajaran', $tahun)
            ->where('is_locked', 1)
            ->countAllResults() > 0;
    }

    /* =========================
     * PREVIEW
     * ========================= */
    public function preview()
    {
        $db = $this->db;

        $now = $db->query("
            SELECT k.kode_kelas, COUNT(m.id) total
            FROM kelas k
            LEFT JOIN murid m ON m.kelas_id = k.id
            GROUP BY k.kode_kelas
            ORDER BY FIELD(k.kode_kelas,'PG','TKA','TKB','1','2','3','4','5','6','LULUS')
        ")->getResultArray();

        $simulasi = [];
        foreach ($this->kelasOrder as $i => $kelas) {
            if ($kelas === 'LULUS') continue;

            $target = $this->kelasOrder[$i + 1];

            $jumlah = $db->query("
                SELECT COUNT(*) total
                FROM murid m
                JOIN kelas k ON k.id = m.kelas_id
                WHERE k.kode_kelas = ?
            ", [$kelas])->getRowArray();

            $simulasi[] = [
                'kelas' => $target,
                'total' => (int) ($jumlah['total'] ?? 0)
            ];
        }

        return view('admin/naik_kelas_preview', [
            'now'          => $now,
            'simulasiNaik' => $simulasi,
            'locked'       => $this->isLocked()
        ]);
    }
    public function histori()
{
    $tahun = $this->request->getGet('tahun_ajaran')
        ?? date('Y').'/'.(date('Y')+1);

    $rows = $this->db->table('kelas_history kh')
        ->select('
            kh.id,
            kh.mode,
            kh.tahun_ajaran,
            kh.executed_at,
            u.nama_depan,
            u.nama_belakang
        ')
        ->join('users u','u.id = kh.executed_by','left')
        ->where('kh.tahun_ajaran', $tahun)
        ->orderBy('kh.executed_at','DESC')
        ->get()
        ->getResultArray();

    return view('admin/naik_kelas_histori', [
        'rows'  => $rows,
        'tahun' => $tahun
    ]);
}


    /* =========================
     * EXECUTE
     * ========================= */
    public function execute()
{
    if (strtoupper((string) $this->request->getMethod()) !== 'POST') {
        return $this->response->setStatusCode(405);
    }

    if ($this->isLocked()) {
        return redirect()->back()->with(
            'error',
            'Kenaikan kelas sudah diproses untuk tahun ajaran ini. Undo terlebih dahulu.'
        );
    }

    $mode = $this->request->getPost('mode');
    if (!in_array($mode, ['naik', 'mundur'])) {
        return redirect()->back()->with('error', 'Mode tidak valid');
    }

    $tahun = date('Y') . '/' . (date('Y') + 1);

    // =========================
    // SNAPSHOT AWAL (UNDO)
    // =========================
    $snapshot = $this->db->table('murid')
        ->select('id, kelas_id')
        ->get()->getResultArray();

    $this->db->transStart();

    /**
     * =========================
     * BUAT TEMP TABLE
     * =========================
     */
    $this->db->query("DROP TEMPORARY TABLE IF EXISTS tmp_naik_kelas");
    $this->db->query("
        CREATE TEMPORARY TABLE tmp_naik_kelas (
            murid_id INT,
            kelas_asal VARCHAR(10),
            kelas_tujuan VARCHAR(10)
        )
    ");

    /**
     * =========================
     * ISI MAPPING (STATIS)
     * =========================
     */
    foreach ($this->kelasOrder as $i => $kelas) {

        if ($mode === 'naik') {
            if ($kelas === 'LULUS') continue;
            $from = $kelas;
            $to   = $this->kelasOrder[$i + 1];
        } else {
            if ($kelas === 'PG') continue;
            $from = $kelas;
            $to   = $this->kelasOrder[$i - 1];
        }

        $this->db->query("
            INSERT INTO tmp_naik_kelas (murid_id, kelas_asal, kelas_tujuan)
            SELECT m.id, k.kode_kelas, ?
            FROM murid m
            JOIN kelas k ON k.id = m.kelas_id
            WHERE k.kode_kelas = ?
        ", [$to, $from]);
    }

    /**
     * =========================
     * UPDATE FINAL (1x SAJA)
     * =========================
     */
    $this->db->query("
        UPDATE murid m
        JOIN tmp_naik_kelas t ON t.murid_id = m.id
        JOIN kelas k_to ON k_to.kode_kelas = t.kelas_tujuan
        SET m.kelas_id = k_to.id
    ");

    /**
     * =========================
     * SIMPAN HISTORI & LOCK
     * =========================
     */
    $this->db->table('kelas_history')->insert([
        'mode'         => $mode,
        'tahun_ajaran' => $tahun,
        'executed_at'  => date('Y-m-d H:i:s'),
        'executed_by'  => session()->get('user_id'),
        'snapshot'     => json_encode($snapshot),
        'is_locked'    => 1
    ]);

    $this->db->transComplete();

    logAudit('execute_naik_kelas', 'warning', [
        'old' => ['mode' => $mode, 'snapshot_count' => count($snapshot)],
        'new' => ['tahun_ajaran' => $tahun, 'locked' => true],
    ]);

    return redirect()->back()->with(
        'success',
        'Proses kenaikan kelas BERHASIL & data aman (tanpa cascading bug)'
    );
}

    /* =========================
     * UNDO
     * ========================= */
    public function undo()
    {
        if (strtoupper((string) $this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405);
        }

        $last = $this->db->table('kelas_history')
            ->orderBy('id', 'DESC')
            ->get(1)->getRowArray();

        if (!$last) {
            return redirect()->back()->with('error', 'Tidak ada histori');
        }

        $rows = json_decode($last['snapshot'], true);

        $this->db->transStart();

        foreach ($rows as $r) {
            $this->db->table('murid')
                ->where('id', $r['id'])
                ->update(['kelas_id' => $r['kelas_id']]);
        }

        // buka lock
        $this->db->table('kelas_history')
            ->where('id', $last['id'])
            ->delete();

        $this->db->transComplete();

        logAudit('undo_naik_kelas', 'warning', [
            'old' => ['history_id' => $last['id'] ?? null, 'tahun_ajaran' => $last['tahun_ajaran'] ?? null],
            'new' => ['undone' => true],
        ]);

        return redirect()->back()->with('success', 'UNDO berhasil & sistem terbuka');
    }
    public function lock()
    {
        if (strtoupper((string) $this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405);
        }

        $tahun = date('Y') . '/' . (date('Y') + 1);

        $last = $this->db->table('kelas_history')
            ->where('tahun_ajaran', $tahun)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        if (!$last) {
            return redirect()->back()->with('error', 'Belum ada histori kenaikan kelas untuk dikunci.');
        }

        $this->db->table('kelas_history')
            ->where('id', $last['id'])
            ->update(['is_locked' => 1]);

        logAudit('lock_naik_kelas', 'warning', [
            'old' => ['history_id' => $last['id'] ?? null, 'is_locked' => $last['is_locked'] ?? 0],
            'new' => ['history_id' => $last['id'] ?? null, 'is_locked' => 1],
        ]);

        return redirect()->back()->with('success', 'Kenaikan kelas berhasil dikunci.');
    }
    public function exportPdf()
{
    $db = $this->db;

    $tahun = $this->request->getGet('tahun_ajaran')
        ?? date('Y').'/'.(date('Y')+1);

    $rows = $db->table('kelas_history kh')
        ->select('
            kh.id,
            kh.mode,
            kh.tahun_ajaran,
            kh.executed_at,
            u.nama_depan,
            u.nama_belakang
        ')
        ->join('users u','u.id = kh.executed_by','left')
        ->where('kh.tahun_ajaran', $tahun)
        ->orderBy('kh.executed_at','DESC')
        ->get()
        ->getResultArray();

    $html = view('admin/naik_kelas_histori_pdf', [
        'rows'  => $rows,
        'tahun' => $tahun
    ]);

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4','portrait');
    $dompdf->render();
    $dompdf->stream(
        'histori_naik_kelas_'.$tahun.'.pdf',
        ['Attachment' => true]
    );
    exit;
}
public function exportCsv()
{
    $tahun = $this->request->getGet('tahun_ajaran')
        ?? date('Y').'/'.(date('Y')+1);

    $rows = $this->db->table('kelas_history kh')
        ->select('
            kh.id,
            kh.mode,
            kh.tahun_ajaran,
            kh.executed_at,
            u.nama_depan,
            u.nama_belakang
        ')
        ->join('users u','u.id = kh.executed_by','left')
        ->where('kh.tahun_ajaran', $tahun)
        ->orderBy('kh.executed_at','DESC')
        ->get()
        ->getResultArray();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=histori_naik_kelas_'.$tahun.'.csv');

    echo "ID,Mode,Tahun Ajaran,Waktu,Eksekutor\n";
    foreach ($rows as $row) {
        $executor = trim(($row['nama_depan'] ?? '').' '.($row['nama_belakang'] ?? ''));
        echo implode(',', [
            $this->escapeCsv((string) ($row['id'] ?? '')),
            $this->escapeCsv(strtoupper((string) ($row['mode'] ?? ''))),
            $this->escapeCsv((string) ($row['tahun_ajaran'] ?? '')),
            $this->escapeCsv((string) ($row['executed_at'] ?? '')),
            $this->escapeCsv($executor),
        ])."\n";
    }
    exit;
}
public function exportSnapshotPdf($id)
{
    $db = $this->db;

    // ambil histori
    $row = $db->table('kelas_history')
        ->where('id', $id)
        ->get()
        ->getRowArray();

    if (!$row) {
        return redirect()->back()->with('error', 'Histori tidak ditemukan');
    }

    // decode snapshot
    $snapshot = json_decode($row['snapshot'], true);

    if (empty($snapshot)) {
        return redirect()->back()->with('error', 'Snapshot kosong');
    }

    $data = $this->buildSnapshotDetailRecords($snapshot);

    // render view PDF
    $html = view('admin/naik_kelas_histori_snapshot_pdf', [
        'row'  => $row,
        'data' => $data
    ]);

    $dompdf = new Dompdf([
        'isRemoteEnabled' => true
    ]);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $dompdf->stream(
        'snapshot_kelas_'.$row['tahun_ajaran'].'.pdf',
        ['Attachment' => true]
    );
    exit;
}
public function exportSnapshotCsv($id)
{
    $row = $this->db->table('kelas_history')
        ->where('id', $id)
        ->get()
        ->getRowArray();

    if (!$row) {
        return redirect()->back()->with('error', 'Histori tidak ditemukan');
    }

    $snapshot = json_decode($row['snapshot'], true);
    if (empty($snapshot)) {
        return redirect()->back()->with('error', 'Snapshot kosong');
    }

    $detail = $this->buildSnapshotDetailRecords($snapshot);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=snapshot_kelas_'.$row['tahun_ajaran'].'.csv');

    echo "Nama,Kelas Snapshot\n";
    foreach ($detail as $item) {
        $nama = trim(($item['nama_depan'] ?? '').' '.($item['nama_belakang'] ?? ''));
        echo implode(',', [
            $this->escapeCsv($nama),
            $this->escapeCsv((string) ($item['kode_kelas'] ?? '-')),
        ])."\n";
    }
    exit;
}
public function historiDetail($id)
{
    $db = $this->db;

    // ambil histori utama
    $row = $db->table('kelas_history')
        ->where('id', $id)
        ->get()
        ->getRowArray();

    if (!$row) {
        return redirect()->back()->with('error', 'Histori tidak ditemukan');
    }

    // decode snapshot
    $snapshot = json_decode($row['snapshot'], true);

    if (empty($snapshot)) {
        return redirect()->back()->with('error', 'Snapshot kosong');
    }

    $detail = $this->buildSnapshotDetailRecords($snapshot);

    return view('admin/naik_kelas_histori_detail', [
        'row'    => $row,
        'detail' => $detail
    ]);
}

private function buildSnapshotDetailRecords(array $snapshot): array
{
    $ids = array_values(array_filter(array_map(static fn ($row) => (int) ($row['id'] ?? 0), $snapshot)));
    $kelasIds = array_values(array_filter(array_map(static fn ($row) => (int) ($row['kelas_id'] ?? 0), $snapshot)));

    if (empty($ids)) {
        return [];
    }

    $muridRows = $this->db->table('murid')
        ->select('id, nama_depan, nama_belakang')
        ->whereIn('id', $ids)
        ->get()
        ->getResultArray();

    $muridMap = [];
    foreach ($muridRows as $murid) {
        $muridMap[(int) $murid['id']] = $murid;
    }

    $kelasMap = [];
    if (!empty($kelasIds)) {
        $kelasRows = $this->db->table('kelas')
            ->select('id, kode_kelas')
            ->whereIn('id', $kelasIds)
            ->get()
            ->getResultArray();

        foreach ($kelasRows as $kelas) {
            $kelasMap[(int) $kelas['id']] = (string) ($kelas['kode_kelas'] ?? '-');
        }
    }

    $detail = [];
    foreach ($snapshot as $row) {
        $muridId = (int) ($row['id'] ?? 0);
        if ($muridId <= 0) {
            continue;
        }

        $murid = $muridMap[$muridId] ?? ['nama_depan' => 'Unknown', 'nama_belakang' => ''];
        $kelasId = (int) ($row['kelas_id'] ?? 0);
        $detail[] = [
            'nama_depan' => $murid['nama_depan'] ?? 'Unknown',
            'nama_belakang' => $murid['nama_belakang'] ?? '',
            'kode_kelas' => $kelasMap[$kelasId] ?? '-',
        ];
    }

    usort($detail, function ($a, $b) {
        return [$a['kode_kelas'] ?? '', $a['nama_depan'] ?? '', $a['nama_belakang'] ?? '']
            <=> [$b['kode_kelas'] ?? '', $b['nama_depan'] ?? '', $b['nama_belakang'] ?? ''];
    });

    return $detail;
}

private function escapeCsv(string $value): string
{
    $escaped = str_replace('"', '""', $value);
    return '"'.$escaped.'"';
}

}
