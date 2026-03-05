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
        $builder = $this->db->table('absensi_detail d')
            ->select('
                d.id as detail_id,
                d.murid_id,
                d.status,
                d.tanggal,
                d.created_at,
                li.nama_lokasi,
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

        $rows = $builder->get()->getResultArray();

        $data = [];
        foreach ($rows as $r) {
            $key = $r['murid_id'].'|'.$r['tanggal'];

            $data[$key]['murid_id']        ??= $r['murid_id'];
            $data[$key]['murid_nama']      ??= $r['murid_nama'];
            $data[$key]['murid_panggilan'] ??= $r['murid_panggilan'];
            $data[$key]['kelas_id']        ??= $r['kelas_id'];
            $data[$key]['kelas_nama']      ??= $r['kelas_nama'];
            $data[$key]['tanggal']         ??= $r['tanggal'];

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
            'tanggal' => $tanggal
        ]);
    }

    /**
     * =========================
     * RESOLVE DOBEL (ADMIN)
     * =========================
     */
    public function resolve()
{
    if (!$this->request->isAJAX()) {
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
    $old = $this->db->table('absensi_detail')
        ->where('murid_id', $muridId)
        ->where('tanggal', $tanggal)
        ->get()
        ->getResultArray();

    // ✔ JADIKAN SATU HADIR
    $this->db->table('absensi_detail')
        ->where('id', $detailId)
        ->update(['status' => 'hadir']);

    // ❌ BATALKAN YANG LAIN
    $this->db->table('absensi_detail')
        ->where('murid_id', $muridId)
        ->where('tanggal', $tanggal)
        ->where('id', '!=', $detailId)
        ->update(['status' => 'batal']);

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
            SELECT COUNT(DISTINCT CONCAT(murid_id, '|', tanggal)) AS total
            FROM absensi_detail
            WHERE status = 'dobel'
        ")->getRowArray();

        return $this->response->setJSON([
            'total' => (int) ($row['total'] ?? 0)
        ]);
    }
}
