<?php 

namespace App\Controllers;

use CodeIgniter\Controller;
use Config\Database;

class Absensi extends Controller
{
    protected $db;

    public function __construct(){
        parent::__construct();
        $this->db = Database::connect();
    }

    /* =====================
       STEP 1
    ===================== */
    public function step1()
    {
        return view('guru/absensi_step1');
    }
    /* =====================
       STEP 2
    ===================== */
    public function tampilkan()
    {
        $kelas  = (array) $this->request->getGet('kelas');
        $lokasi = $this->request->getGet('lokasi');

        if (empty($kelas) || empty($lokasi)) {
            return redirect()->to('guru/absensi');
        }

        $murid = $this->db->table('murid')
            ->select('id, nama_depan, nama_belakang, panggilan, kelas_id, status, foto')
            ->whereIn('kelas_id', $kelas)
            ->where('status', 'aktif')
            ->orderBy('kelas_id','ASC')
            ->orderBy('nama_depan','ASC')
            ->get()->getResultArray();

        return view('guru/absensi_step2', [
            'kelas_id'  => $kelas,
            'lokasi_id' => $lokasi,
            'murid'     => $murid
        ]);
    }

    /* =====================
       SIMPAN ABSENSI (FINAL FIX)
    ===================== */
    public function simpan()
{
    try {
        $tanggal  = date('Y-m-d');
        $jam      = date('H:i:s');
        $guruId   = session('user_id');
        $guruNama = trim(session('nama_depan').' '.session('nama_belakang'));
        $lokasiId = (int) $this->request->getPost('lokasi_id');
        $murids   = $this->request->getPost('hadir') ?? [];

        if (!$murids) {
            return $this->response->setJSON([
                'status'=>'error',
                'message'=>'Tidak ada murid dipilih'
            ]);
        }

        /* ===== LOKASI ===== */
        $lokasi = $this->db->table('lokasi_ibadah')
            ->where('id', $lokasiId)
            ->get()->getRowArray();

        $lokasiText = $lokasi['nama_lokasi'] ?? '-';

        /* ===== SELFIE ===== */
        $selfie = $this->request->getFile('selfie');
        if (!$selfie || !$selfie->isValid()) {
            return $this->response->setJSON([
                'status'=>'error',
                'message'=>'Selfie wajib'
            ]);
        }

        $path = FCPATH.'uploads/selfie/';
        if (!is_dir($path)) mkdir($path, 0777, true);

        $selfieName = 'selfie_'.$guruId.'_'.time().'.jpg';
        if (!$selfie->move($path, $selfieName)) {
            return $this->response->setJSON([
                'status'=>'error',
                'message'=>'Gagal upload selfie'
            ]);
        }

        /* ===== TRANSAKSI ===== */
        $this->db->transBegin();

        // HEADER ABSENSI
        $this->db->table('absensi')->insert([
            'guru_id'     => $guruId,
            'lokasi_id'   => $lokasiId,
            'lokasi_text' => $lokasiText,
            'tanggal'     => $tanggal,
            'jam'         => $jam,
            'selfie_foto' => $selfieName
        ]);

        $absensiId = $this->db->insertID();
        $dobel = [];

        foreach ($murids as $muridId) {

            // CEK DOBEL (murid + tanggal, TANPA peduli lokasi/jam)
            $exist = $this->db->table('absensi_detail')
                ->where('murid_id', $muridId)
                ->where('tanggal', $tanggal)
                ->countAllResults() > 0;

            $status = $exist ? 'dobel' : 'hadir';

            // INSERT DETAIL (INI YANG TADI HILANG)
            $this->db->table('absensi_detail')->insert([
                'absensi_id' => $absensiId,
                'murid_id'   => $muridId,
                'status'     => $status,
                'tanggal'    => $tanggal
            ]);
            $absensiDetailId = (int) $this->db->insertID();

            // LOG
            $this->db->table('absensi_log')->insert([
                'absensi_detail_id' => $absensiDetailId,
                'aksi'        => 'create',
                'status_baru' => $status,
                'oleh'        => 'guru',
                'user_id'     => $guruId
            ]);

            // AMBIL DETAIL ABSENSI PERTAMA (UNTUK POPUP)
            if ($status === 'dobel') {
                $first = $this->db->table('absensi_detail d')
                    ->select("
                        m.nama_depan,
                        m.nama_belakang,
                        k.nama_kelas,
                        a.lokasi_text,
                        a.jam,
                        CONCAT(u.nama_depan,' ',u.nama_belakang) AS guru_pertama
                    ")
                    ->join('murid m','m.id=d.murid_id')
                    ->join('kelas k','k.id=m.kelas_id')
                    ->join('absensi a','a.id=d.absensi_id')
                    ->join('users u','u.id=a.guru_id')
                    ->where('d.murid_id', $muridId)
                    ->where('d.tanggal', $tanggal)
                    ->orderBy('a.jam','ASC')
                    ->get()->getRowArray();

                $dobel[] = [
                    'murid_id' => $muridId,
                    'detail'   => $first
                ];
            }
        }

        if ($this->db->transStatus() === false) {
            $this->db->transRollback();
            throw new \Exception('DB error');
        }

        $this->db->transCommit();

        return $this->response->setJSON([
            'status'  => empty($dobel) ? 'success' : 'duplicate',
            'tanggal'=> $tanggal,
            'guru'   => $guruNama,
            'dobel'  => $dobel
        ]);

    } catch (\Throwable $e) {
        return $this->response->setJSON([
            'status'=>'error',
            'message'=>$e->getMessage()
        ]);
    }
}

    /* =====================
       ABSENSI HARI INI
    ===================== */
    public function hariIni()
{
    $tanggal = date('Y-m-d');
    $guruId  = session('user_id');

    // Ambil absensi terakhir hari ini
    $absensi = $this->db->table('absensi')
        ->where('guru_id', $guruId)
        ->where('tanggal', $tanggal)
        ->orderBy('jam', 'DESC')
        ->get()
        ->getRowArray();

    if (!$absensi) {
        return view('guru/absensi_hari_ini', [
            'absensi' => null,
            'detail'  => []
        ]);
    }

    // Detail murid
    $detail = $this->db->table('absensi_detail d')
  ->select('
    d.murid_id,
    d.status,
    m.nama_depan,
    m.nama_belakang,
    m.panggilan,
    m.kelas_id,
    k.nama_kelas,
    m.foto
  ')
  ->join('murid m', 'm.id = d.murid_id')
  ->join('kelas k', 'k.id = m.kelas_id')
  ->where('d.absensi_id', $absensi["id"])
  ->orderBy('m.nama_depan', 'ASC')
  ->get()
  ->getResultArray();

    // hitung ringkasan
$hadir = 0;
$dobel = 0;

foreach ($detail as $d) {
    if ($d['status'] === 'hadir') {
        $hadir++;
    } elseif ($d['status'] === 'dobel') {
        $dobel++;
    }
}

return view('guru/absensi_hari_ini', [
    'absensi' => $absensi,
    'detail'  => $detail,
    'hadir'   => $hadir,
    'dobel'   => $dobel
]);

}

public function simpanEditHariIni()
{
    $absensiId = (int) $this->request->getPost('absensi_id');
    $hadirRaw  = $this->request->getPost('hadir');
    $hadirIds  = array_values(array_unique(array_filter(
        array_map('intval', (array) $hadirRaw),
        static fn($v) => $v > 0
    )));
    $userId    = (int) session('user_id');

    if (! $absensiId) {
        return redirect()->back()->with('error', 'Absensi tidak valid');
    }

    $owner = $this->db->table('absensi')
        ->select('id')
        ->where('id', $absensiId)
        ->where('guru_id', $userId)
        ->get()
        ->getRowArray();

    if (! $owner) {
        return redirect()->back()->with('error', 'Absensi tidak ditemukan atau bukan milik Anda');
    }

    $this->db->transBegin();

    $before = $this->db->table('absensi_detail')
        ->select('id, murid_id, status')
        ->where('absensi_id', $absensiId)
        ->get()->getResultArray();

    $this->db->table('absensi_detail')
        ->where('absensi_id', $absensiId)
        ->update(['status' => 'dobel']);

    if (! empty($hadirIds)) {
        $this->db->table('absensi_detail')
            ->where('absensi_id', $absensiId)
            ->whereIn('murid_id', $hadirIds)
            ->update(['status' => 'hadir']);
    }

    $after = $this->db->table('absensi_detail')
        ->select('id, murid_id, status')
        ->where('absensi_id', $absensiId)
        ->get()->getResultArray();

    $changed = 0;
    foreach ($after as $row) {
        $old = array_filter(
            $before,
            fn($b) => $b['murid_id'] == $row['murid_id']
        );

        $oldStatus = $old ? array_values($old)[0]['status'] : null;

        if ($oldStatus !== $row['status']) {
            $changed++;
            logAudit(
                'update_absensi',
                'info',
                [
                    'murid_id'   => $row['murid_id'],
                    'absensi_id' => $absensiId,
                    'old'        => ['status' => $oldStatus],
                    'new'        => ['status' => $row['status']],
                ]
            );
        }
    }

    if ($this->db->transStatus() === false) {
        $this->db->transRollback();
        return redirect()->back()->with('error', 'Gagal menyimpan perubahan');
    }

    $this->db->transCommit();

    if ($changed === 0) {
        return redirect()
            ->to(base_url('guru/absensi-hari-ini'))
            ->with('error', 'Tidak ada perubahan status yang tersimpan');
    }

    return redirect()
        ->to(base_url('guru/absensi-hari-ini'))
        ->with('success', 'Perubahan absensi berhasil disimpan');
}

}
