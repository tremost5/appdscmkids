<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Config\Database;

class AdminAbsensiDobel extends Controller
{
    protected $db;

    public function __construct(){
        parent::__construct();
        $this->db = Database::connect();
    }

    /**
     * =========================
     * LIST ABSENSI DOBEL (FINAL)
     * =========================
     */
    public function index()
    {
        $tanggal = $this->request->getGet('tanggal');
        $mode = $this->resolvePresensiMode($this->request->getGet('mode'));
        $builder = $this->db->table('absensi_detail d')
            ->select('
                d.id as detail_id,
                d.murid_id,
                d.status,
                d.tanggal,
                d.created_at,
                COALESCE(a.jenis_presensi, "reguler") AS jenis_presensi,
                a.keterangan,
                COALESCE(NULLIF(a.keterangan, ""), NULLIF(a.lokasi_text, ""), li.nama_lokasi) AS nama_lokasi,
                u.nama_depan as guru,
                m.nama_depan as murid_nama,
                m.panggilan as murid_panggilan,
                m.kelas_id,
                k.nama_kelas as kelas_nama
            ')
            ->join('absensi a', 'a.id = d.absensi_id')
            ->join('lokasi_ibadah li', 'li.id = a.lokasi_id', 'left')
            ->join('users u', 'u.id = a.guru_id')
            ->join('murid m', 'm.id = d.murid_id')
            ->join('kelas k', 'k.id = m.kelas_id', 'left')
            ->where('d.status', 'dobel')
            ->orderBy('d.tanggal', 'DESC')
            ->orderBy('d.created_at', 'ASC');

        if ($tanggal) {
            $builder->where('d.tanggal', $tanggal);
        }
        if ($mode === 'reguler') {
            $builder->groupStart()
                ->where('a.jenis_presensi', 'reguler')
                ->orWhere('a.jenis_presensi', null)
                ->groupEnd();
        } elseif ($mode === 'unity') {
            $builder->where('a.jenis_presensi', 'unity');
        }

        $rows = $builder->get()->getResultArray();

        $data = [];
        foreach ($rows as $r) {
            $key = $r['murid_id'].'|'.$r['tanggal'].'|'.$r['jenis_presensi'];
            $r['nama_lokasi'] = formatLokasiDisplay($r['nama_lokasi'] ?? '-', $r['keterangan'] ?? null);

            $data[$key]['murid_id']        ??= $r['murid_id'];
            $data[$key]['murid_nama']      ??= $r['murid_nama'];
            $data[$key]['murid_panggilan'] ??= $r['murid_panggilan'];
            $data[$key]['kelas_id']        ??= $r['kelas_id'];
            $data[$key]['kelas_nama']      ??= $r['kelas_nama'];
            $data[$key]['tanggal']         ??= $r['tanggal'];
            $data[$key]['jenis_presensi']  ??= $r['jenis_presensi'];

            if (!isset($data[$key]['murid_display'])) {
                $namaLengkap = trim($r['murid_nama'] ?? '');
                $data[$key]['murid_display'] = !empty($r['murid_panggilan'])
                    ? $r['murid_panggilan'].' ('.$namaLengkap.')'
                    : $namaLengkap;
            }

            $data[$key]['items'][] = $r;
        }

        return view('admin/absensi_dobel', [
            'data'    => $data,
            'tanggal' => $tanggal,
            'mode'    => $mode
        ]);
    }

    /**
     * =========================
     * RESOLVE DOBEL (ADMIN)
     * =========================
     */
    public function resolve()
{
    if (!$this->request->isAJAX() || strtoupper((string) $this->request->getMethod()) !== 'POST') {
        return $this->response->setStatusCode(403);
    }

    helper('audit');

    $detailId = $this->request->getPost('detail_id');
    $muridId  = $this->request->getPost('murid_id');
    $tanggal  = $this->request->getPost('tanggal');

    if (!$detailId || !$muridId || !$tanggal) {
        return $this->response->setJSON(['status' => 'error']);
    }

    $this->db->transBegin();

    // 🔍 ambil data lama (opsional tapi bagus untuk audit)
    $selected = $this->db->table('absensi_detail d')
        ->select('d.id, d.absensi_id, COALESCE(a.jenis_presensi, "reguler") AS jenis_presensi')
        ->join('absensi a', 'a.id = d.absensi_id')
        ->where('d.id', $detailId)
        ->where('d.murid_id', $muridId)
        ->where('d.tanggal', $tanggal)
        ->get()
        ->getRowArray();

    if (!$selected) {
        $this->db->transRollback();
        return $this->response->setJSON(['status' => 'error']);
    }

    $jenisPresensi = (string) ($selected['jenis_presensi'] ?? 'reguler');

    $conflictRows = $this->db
        ->query(
            "SELECT d.id
             FROM absensi_detail d
             JOIN absensi a ON a.id = d.absensi_id
             WHERE d.murid_id = ?
               AND d.tanggal = ?
               AND COALESCE(a.jenis_presensi, 'reguler') = ?",
            [$muridId, $tanggal, $jenisPresensi]
        )
        ->getResultArray();

    $conflictIds = array_values(array_filter(array_map(
        static fn ($row) => (int) ($row['id'] ?? 0),
        $conflictRows
    )));

    $old = empty($conflictIds)
        ? []
        : $this->db->table('absensi_detail')
            ->whereIn('id', $conflictIds)
            ->get()
            ->getResultArray();

    // ✔ JADIKAN SATU HADIR
    $this->db->table('absensi_detail')
        ->where('id', $detailId)
        ->update(['status' => 'hadir']);

    // ❌ BATALKAN YANG LAIN
    if (!empty($conflictIds)) {
        $cancelIds = array_values(array_filter($conflictIds, static fn ($id) => $id !== (int) $detailId));
        if (!empty($cancelIds)) {
            $this->db->table('absensi_detail')
                ->whereIn('id', $cancelIds)
                ->update(['status' => 'batal']);
        }
    }

    if ($this->db->transStatus() === false) {
        $this->db->transRollback();
        return $this->response->setJSON(['status' => 'error']);
    }

    $this->db->transCommit();

    // ✅ AUDIT LOG (ADMIN)
    logAudit(
        'resolve_dobel',
        'warning',
        [
            'murid_id'   => $muridId,
            'absensi_id' => $detailId,
            'old'        => $old,
            'new'        => ['status' => 'hadir']
        ]
    );

    return $this->response->setJSON([
        'status' => 'ok',
        'csrf'   => [
            'name' => csrf_token(),
            'hash' => csrf_hash()
        ]
    ]);
}

    /**
     * =========================
     * COUNT DOBEL (BADGE)
     * =========================
     */
    public function count()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $row = $this->db->query("
            SELECT
                COUNT(DISTINCT CONCAT(d.murid_id, '|', d.tanggal, '|', COALESCE(a.jenis_presensi, 'reguler'))) AS total,
                COUNT(DISTINCT CASE WHEN COALESCE(a.jenis_presensi, 'reguler') = 'reguler'
                    THEN CONCAT(d.murid_id, '|', d.tanggal, '|', COALESCE(a.jenis_presensi, 'reguler')) END) AS reguler,
                COUNT(DISTINCT CASE WHEN COALESCE(a.jenis_presensi, 'reguler') = 'unity'
                    THEN CONCAT(d.murid_id, '|', d.tanggal, '|', COALESCE(a.jenis_presensi, 'reguler')) END) AS unity
            FROM absensi_detail d
            JOIN absensi a ON a.id = d.absensi_id
            WHERE d.status = 'dobel'
        ")->getRowArray();

        return $this->response->setJSON([
            'total' => (int) ($row['total'] ?? 0),
            'reguler' => (int) ($row['reguler'] ?? 0),
            'unity' => (int) ($row['unity'] ?? 0),
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
