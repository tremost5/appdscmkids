<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\MuridModel;
use App\Models\MateriAjarModel;
use App\Services\AbsensiService;
use CodeIgniter\I18n\Time;

class Dashboard extends BaseController
{
    protected $db;
    protected $userModel;
    protected $muridModel;
    protected $materiModel;
    protected $absensiService;

    public function __construct(){
        parent::__construct();
        $this->db           = \Config\Database::connect();
        $this->userModel    = new UserModel();
        $this->muridModel   = new MuridModel();
        $this->materiModel  = new MateriAjarModel();
        $this->absensiService = app(AbsensiService::class);
    }

    /* =====================================================
       ROUTING UTAMA
    ===================================================== */
    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        return match (session()->get('role_id')) {
            1 => redirect()->to('/dashboard/superadmin'),
            2 => redirect()->to('/dashboard/admin'),
            3 => redirect()->to('/dashboard/guru'),
            default => redirect()->to('/logout'),
        };
    }

    /* =====================================================
       DASHBOARD GURU
    ===================================================== */
public function guru()
{
    $userId = session('user_id');
    if (!$userId) {
        return redirect()->to('/login');
    }

    $db        = \Config\Database::connect();
    $userModel = new \App\Models\UserModel();
    $muridModel = new \App\Models\MuridModel();

    // ==========================
    // UPDATE LAST LOGIN
    // ==========================
    $userModel->update($userId, [
        'last_login' => date('Y-m-d H:i:s')
    ]);

    $guru = $userModel->find($userId);

    // ==========================
    // 🎂 ULTAH MURID (H-3 s/d H+3)
    // ==========================
    $ultah = $muridModel
        ->select('murid.*, kelas.nama_kelas')
        ->join('kelas', 'kelas.id = murid.kelas_id', 'left')
        ->where("
            DAYOFYEAR(murid.tanggal_lahir)
            BETWEEN DAYOFYEAR(CURDATE())-3
            AND DAYOFYEAR(CURDATE())+3
        ")
        ->orderBy('DAYOFYEAR(murid.tanggal_lahir)', 'ASC')
        ->findAll();

    foreach ($ultah as &$u) {
        $ultahDate = \CodeIgniter\I18n\Time::createFromFormat('Y-m-d', $u['tanggal_lahir'])
            ->setYear(date('Y'));
        $today = \CodeIgniter\I18n\Time::today();
        $days  = $today->difference($ultahDate)->getDays();
        if ($ultahDate->isBefore($today)) $days = -$days;
        $u['h_minus'] = $days;
    }

    // ==========================
    // 🏆 RANKING HADIR (TOP 5)
    // ==========================
    $ranking = $db->query("
        SELECT m.id, m.nama_depan, m.nama_belakang, m.foto,
               COUNT(a.id) AS total_hadir
        FROM murid m
        JOIN absensi_detail a ON a.murid_id = m.id
        WHERE a.status = 'hadir'
          AND MONTH(a.tanggal) = MONTH(CURDATE())
          AND YEAR(a.tanggal) = YEAR(CURDATE())
        GROUP BY m.id, m.nama_depan, m.nama_belakang, m.foto
        ORDER BY total_hadir DESC
        LIMIT 5
    ")->getResultArray();

    // ==========================
    // 📚 MATERI TERBARU (GLOBAL)
    // ==========================
    $materi = $db->table('materi_ajar m')
        ->select('m.*, k.nama_kelas')
        ->join('kelas k', 'k.id = m.kelas_id', 'left')
        ->orderBy('m.created_at', 'DESC')
        ->limit(3)
        ->get()
        ->getResultArray();

    // ==========================
    // 📈 KEHADIRAN: 7 HARI TERAKHIR (PER GURU)
    // ==========================
    $weeklyRows = $db->table('absensi_detail d')
        ->select('a.tanggal, COUNT(d.id) as total')
        ->join('absensi a', 'a.id = d.absensi_id', 'inner')
        ->where('a.guru_id', $userId)
        ->where('d.status', 'hadir')
        ->where('a.tanggal >=', date('Y-m-d', strtotime('-6 days')))
        ->where('a.tanggal <=', date('Y-m-d'))
        ->groupBy('a.tanggal')
        ->orderBy('a.tanggal', 'ASC')
        ->get()
        ->getResultArray();

    $weeklyMap = [];
    foreach ($weeklyRows as $row) {
        $weeklyMap[$row['tanggal']] = (int) $row['total'];
    }

    $weeklyLabels = [];
    $weeklyData = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $weeklyLabels[] = date('D', strtotime($date));
        $weeklyData[] = $weeklyMap[$date] ?? 0;
    }

    // ==========================
    // 📊 KEHADIRAN: BULAN BERJALAN (PER GURU)
    // ==========================
    $monthStart = date('Y-m-01');
    $today = date('Y-m-d');

    $monthlyRows = $db->table('absensi_detail d')
        ->select('a.tanggal, COUNT(d.id) as total')
        ->join('absensi a', 'a.id = d.absensi_id', 'inner')
        ->where('a.guru_id', $userId)
        ->where('d.status', 'hadir')
        ->where('a.tanggal >=', $monthStart)
        ->where('a.tanggal <=', $today)
        ->groupBy('a.tanggal')
        ->orderBy('a.tanggal', 'ASC')
        ->get()
        ->getResultArray();

    $monthlyMap = [];
    foreach ($monthlyRows as $row) {
        $monthlyMap[$row['tanggal']] = (int) $row['total'];
    }

    $monthlyLabels = [];
    $monthlyData = [];
    $daysInRange = (int) date('j');
    for ($d = 1; $d <= $daysInRange; $d++) {
        $date = date('Y-m-') . str_pad((string) $d, 2, '0', STR_PAD_LEFT);
        $monthlyLabels[] = (string) $d;
        $monthlyData[] = $monthlyMap[$date] ?? 0;
    }

    $todayCount = $weeklyData[6] ?? 0;
    $weeklyTotal = array_sum($weeklyData);
    $monthlyTotal = array_sum($monthlyData);
    $avgWeekly = $weeklyTotal > 0 ? round($weeklyTotal / 7, 1) : 0;

    $unitySummary = [];
    if ($this->hasTableColumn('murid', 'unity')) {
        $unitySummary = $db->table('murid')
            ->select('unity, COUNT(id) as total')
            ->where('status', 'aktif')
            ->where('unity IS NOT NULL', null, false)
            ->where('unity <>', '')
            ->groupBy('unity')
            ->orderBy('unity', 'ASC')
            ->get()
            ->getResultArray();
    }

    // ==========================
    // 🔔 NOTIF DASHBOARD
    // ==========================
    $notif = [];

    if (!empty($materi)) {
        $notif[] = [
            'icon' => '📚',
            'text' => 'Ada materi ajar baru dari admin'
        ];
    }

    if (!empty($ultah)) {
        $notif[] = [
            'icon' => '🎂',
            'text' => 'Ada murid ulang tahun'
        ];
    }

    // ==========================
    // TEMPLATE WA
    // ==========================
    $templateWaUltah = session()->get('template_wa_ultah') ??
"Selamat ulang tahun 🎉 {nama} dari kelas {kelas}.
Semoga Panjang Umur Sehat Selalu Berprestasi dan Makin Cinta Tuhan.
Tuhan Yesus Memberkati {nama} Selalu 😊
– {guru}";

    return view('dashboard/guru', [
        'guru'            => $guru,
        'ultah'           => $ultah,
        'ranking'         => $ranking,
        'materi'          => $materi,
        'notif'           => $notif,
        'templateWaUltah' => $templateWaUltah,
        'weeklyLabels'    => $weeklyLabels,
        'weeklyData'      => $weeklyData,
        'monthlyLabels'   => $monthlyLabels,
        'monthlyData'     => $monthlyData,
        'todayCount'      => $todayCount,
        'weeklyTotal'     => $weeklyTotal,
        'monthlyTotal'    => $monthlyTotal,
        'avgWeekly'       => $avgWeekly,
        'unitySummary'    => $unitySummary,
    ]);
}


    /* =====================================================
       DASHBOARD ADMIN
    ===================================================== */
    public function admin()
{
    $now = time();
    $today = date('Y-m-d');

    /* ===============================
       ABSENSI DOBEL HARI INI
    =============================== */
    $dobelHariIni = $this->absensiService->unresolvedDoubleCount($today);

    $usersFields = array_map('strtolower', $this->db->getFieldNames('users'));
    $hasUserCreatedAt = in_array('created_at', $usersFields, true);
    $todayStart = $today . ' 00:00:00';

    $guruNonaktifCount = (int) $this->db->table('users')
        ->where('role_id', 3)
        ->where('status', 'nonaktif')
        ->countAllResults();

    $guruBaruHariIniCount = 0;
    $guruBaruHariIniList = [];
    if ($hasUserCreatedAt) {
        $guruBaruHariIniCount = (int) $this->db->table('users')
            ->where('role_id', 3)
            ->where('created_at >=', $todayStart)
            ->countAllResults();

        $guruBaruHariIniList = $this->db->table('users')
            ->select('nama_depan, nama_belakang, created_at, status')
            ->where('role_id', 3)
            ->where('created_at >=', $todayStart)
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();
    }

    /* ===============================
       STATUS GURU
    =============================== */
    $guru = $this->userModel
        ->select('last_login')
        ->where('role_id', 3)
        ->findAll();

    $total = count($guru);
    $online = $idle = $offline = 0;

    foreach ($guru as $g) {
        if (!$g['last_login']) {
            $offline++;
            continue;
        }

        $diff = $now - strtotime($g['last_login']);

        if ($diff <= 300) {
            $online++;
        } elseif ($diff <= 900) {
            $idle++;
        } else {
            $offline++;
        }
    }

    /* ===============================
       🎂 ULANG TAHUN GURU (H-3 s/d H+3)
    =============================== */
    $ultahGuru = $this->userModel
        ->where('role_id', 3)
        ->where('tanggal_lahir IS NOT NULL', null, false)
        ->where("
            DAYOFYEAR(tanggal_lahir)
            BETWEEN DAYOFYEAR(CURDATE())-3
            AND DAYOFYEAR(CURDATE())+3
        ")
        ->orderBy('DAYOFYEAR(tanggal_lahir)', 'ASC')
        ->findAll();

    foreach ($ultahGuru as &$g) {
        $usia = date('Y') - date('Y', strtotime($g['tanggal_lahir']));
        $g['usia'] = $usia;
    }

    /* ===============================
       📚 MATERI MINGGU INI
    =============================== */
    $materiMingguIni = $this->db->table('materi_ajar m')
        ->select('m.*, k.nama_kelas')
        ->join('kelas k', 'k.id = m.kelas_id', 'left')
        ->where('m.created_at >=', date('Y-m-d', strtotime('-7 days')))
        ->orderBy('m.created_at', 'DESC')
        ->limit(5)
        ->get()
        ->getResultArray();

    /* ===============================
       GRAFIK HADIR MINGGU INI
    =============================== */
    $weeklyRows = $this->db->table('absensi_detail')
        ->select('tanggal, COUNT(id) as total')
        ->where('status', 'hadir')
        ->where('tanggal >=', date('Y-m-d', strtotime('-6 days')))
        ->where('tanggal <=', $today)
        ->groupBy('tanggal')
        ->orderBy('tanggal', 'ASC')
        ->get()
        ->getResultArray();

    $weeklyMap = [];
    foreach ($weeklyRows as $row) {
        $weeklyMap[$row['tanggal']] = (int) $row['total'];
    }

    $weeklyLabels = [];
    $weeklyData = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $weeklyLabels[] = date('D', strtotime($date));
        $weeklyData[] = $weeklyMap[$date] ?? 0;
    }

    /* ===============================
       GRAFIK HADIR BULAN INI
    =============================== */
    $monthStart = date('Y-m-01');
    $monthlyRows = $this->db->table('absensi_detail')
        ->select('tanggal, COUNT(id) as total')
        ->where('status', 'hadir')
        ->where('tanggal >=', $monthStart)
        ->where('tanggal <=', $today)
        ->groupBy('tanggal')
        ->orderBy('tanggal', 'ASC')
        ->get()
        ->getResultArray();

    $monthlyMap = [];
    foreach ($monthlyRows as $row) {
        $monthlyMap[$row['tanggal']] = (int) $row['total'];
    }

    $monthlyLabels = [];
    $monthlyData = [];
    $daysInRange = (int) date('j');
    for ($d = 1; $d <= $daysInRange; $d++) {
        $date = date('Y-m-') . str_pad((string) $d, 2, '0', STR_PAD_LEFT);
        $monthlyLabels[] = (string) $d;
        $monthlyData[] = $monthlyMap[$date] ?? 0;
    }

    $todayHadir = $weeklyData[6] ?? 0;
    $totalHadirMinggu = array_sum($weeklyData);
    $totalHadirBulan = array_sum($monthlyData);
    $avgHarian = $totalHadirMinggu > 0 ? round($totalHadirMinggu / 7, 1) : 0;

    $unitySummary = [];
    if ($this->hasTableColumn('murid', 'unity')) {
        $unitySummary = $this->db->table('murid')
            ->select('unity, COUNT(id) as total')
            ->where('status', 'aktif')
            ->where('unity IS NOT NULL', null, false)
            ->where('unity <>', '')
            ->groupBy('unity')
            ->orderBy('unity', 'ASC')
            ->get()
            ->getResultArray();
    }

    return view('dashboard/admin', [
        'total_guru'      => $total,
        'guru_online'     => $online,
        'guru_idle'       => $idle,
        'guru_offline'    => $offline,
        'dobelHariIni'    => $dobelHariIni,
        'guruNonaktifCount' => $guruNonaktifCount,
        'guruBaruHariIniCount' => $guruBaruHariIniCount,
        'guruBaruHariIniList' => $guruBaruHariIniList,
        'materiMingguIni' => $materiMingguIni,
        'ultahGuru'       => $ultahGuru,
        'weeklyLabels'    => $weeklyLabels,
        'weeklyData'      => $weeklyData,
        'monthlyLabels'   => $monthlyLabels,
        'monthlyData'     => $monthlyData,
        'todayHadir'      => $todayHadir,
        'totalHadirMinggu'=> $totalHadirMinggu,
        'totalHadirBulan' => $totalHadirBulan,
        'avgHarian'       => $avgHarian,
        'unitySummary'    => $unitySummary,
    ]);
}

    /* =====================================================
       JSON – GURU ONLINE (AJAX)
    ===================================================== */
    public function guruOnlineJson()
    {
        $now = time();

        $guru = $this->userModel
            ->select('nama_depan, nama_belakang, last_login')
            ->where('role_id', 3)
            ->where('last_login IS NOT NULL', null, false)
            ->findAll();

        $online = [];

        foreach ($guru as $g) {
            if (($now - strtotime($g['last_login'])) <= 300) {
                $online[] = [
                    'nama'       => trim($g['nama_depan'].' '.$g['nama_belakang']),
                    'last_login' => date('H:i', strtotime($g['last_login']))
                ];
            }
        }

        return $this->response->setJSON($online);
    }

    /* =====================================================
       DASHBOARD SUPERADMIN
    ===================================================== */
    public function superadmin()
    {
        return redirect()->to('/superadmin/dashboard');
    }
}
