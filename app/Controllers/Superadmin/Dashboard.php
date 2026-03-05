<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use Config\Database;

class Dashboard extends BaseController
{
    public function index()
    {
        try {
        $db = Database::connect();
        $hasJenisPresensi = $this->hasTableColumn('absensi', 'jenis_presensi');

        // ===== USER STATS =====
        $total_users = $db->table('users')->countAllResults();

        $user_online = $db->table('users')
            ->where('last_seen >=', date('Y-m-d H:i:s', strtotime('-5 minutes')))
            ->countAllResults();

        // ===== ABSENSI STATS =====
        $today = date('Y-m-d');

        $absenHariIniBuilder = $db->table('absensi')
            ->where('tanggal', $today);
        if ($hasJenisPresensi) {
            $absenHariIniBuilder->where('jenis_presensi', 'unity');
        }
        $absen_unity_hari_ini = $absenHariIniBuilder->countAllResults();
        $absen_hari_ini = $db->table('absensi')
            ->where('tanggal', $today)
            ->countAllResults();

        $absenDobelBuilder = $db->table('absensi_detail ad')
            ->join('absensi a', 'a.id = ad.absensi_id')
            ->where('ad.tanggal', $today)
            ->where('ad.status', 'dobel');
        if ($hasJenisPresensi) {
            $absenDobelBuilder->where('a.jenis_presensi', 'unity');
        }
        $absen_dobel_unity = $absenDobelBuilder->countAllResults();
        $absen_dobel = $db->table('absensi_detail ad')
            ->where('ad.tanggal', $today)
            ->where('ad.status', 'dobel')
            ->countAllResults();

        $total_murid = $db->table('murid')->countAllResults();
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

        // ===== GRAFIK HADIR MINGGU INI (GLOBAL) =====
        $weeklyRows = $db->table('absensi_detail ad')
            ->select('a.tanggal, COUNT(ad.id) as total')
            ->join('absensi a', 'a.id = ad.absensi_id')
            ->where('ad.status', 'hadir')
            ->where('a.tanggal >=', date('Y-m-d', strtotime('-6 days')))
            ->where('a.tanggal <=', $today);
        $weeklyRows = $weeklyRows
            ->groupBy('a.tanggal')
            ->orderBy('a.tanggal', 'ASC')
            ->get()
            ->getResultArray();
        $weeklyUnityRows = [];
        if ($hasJenisPresensi) {
            $weeklyUnityRows = $db->table('absensi_detail ad')
                ->select('a.tanggal, COUNT(ad.id) as total')
                ->join('absensi a', 'a.id = ad.absensi_id')
                ->where('ad.status', 'hadir')
                ->where('a.jenis_presensi', 'unity')
                ->where('a.tanggal >=', date('Y-m-d', strtotime('-6 days')))
                ->where('a.tanggal <=', $today)
                ->groupBy('a.tanggal')
                ->orderBy('a.tanggal', 'ASC')
                ->get()
                ->getResultArray();
        }

        $weeklyMap = [];
        foreach ($weeklyRows as $row) {
            $weeklyMap[$row['tanggal']] = (int) $row['total'];
        }
        $weeklyUnityMap = [];
        foreach ($weeklyUnityRows as $row) {
            $weeklyUnityMap[$row['tanggal']] = (int) $row['total'];
        }

        $weeklyLabels = [];
        $weeklyData = [];
        $weeklyUnityData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $weeklyLabels[] = date('D', strtotime($date));
            $weeklyData[] = $weeklyMap[$date] ?? 0;
            $weeklyUnityData[] = $weeklyUnityMap[$date] ?? 0;
        }

        // ===== DISTRIBUSI ROLE =====
        $roleRaw = $db->table('users')
            ->select('role_id, COUNT(id) as total')
            ->groupBy('role_id')
            ->get()
            ->getResultArray();

        $roleMap = [1 => 0, 2 => 0, 3 => 0];
        foreach ($roleRaw as $r) {
            $roleId = (int) ($r['role_id'] ?? 0);
            if (isset($roleMap[$roleId])) {
                $roleMap[$roleId] = (int) $r['total'];
            }
        }

        // ===== SUPERADMIN LOG =====
        $activity = $db->table('superadmin_log')
            ->select('action AS aksi, description AS deskripsi, created_at')
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        // ===== MAINTENANCE STATUS =====
        $maintenanceRow = $db->table('system_settings')
            ->where('setting_key', 'maintenance_mode')
            ->get()
            ->getRowArray();
        $maintenanceValue = (string) ($maintenanceRow['value'] ?? ($maintenanceRow['setting_value'] ?? '0'));
        $maintenanceActive = $maintenanceValue === '1';

        logSuperadmin('view_dashboard', 'Superadmin membuka dashboard');

        return view('superadmin/dashboard', [
            'total_users'    => $total_users,
            'user_online'    => $user_online,
            'absen_hari_ini' => $absen_hari_ini,
            'absen_unity_hari_ini' => $absen_unity_hari_ini,
            'absen_dobel'    => $absen_dobel,
            'absen_dobel_unity' => $absen_dobel_unity,
            'total_murid'    => $total_murid,
            'weeklyLabels'   => $weeklyLabels,
            'weeklyData'     => $weeklyData,
            'weeklyUnityData'=> $weeklyUnityData,
            'roleLabels'     => ['Superadmin', 'Admin', 'Guru'],
            'roleData'       => [$roleMap[1], $roleMap[2], $roleMap[3]],
            'activity'       => $activity,
            'maintenanceActive' => $maintenanceActive,
            'unitySummary'   => $unitySummary,
        ]);
        } catch (\Throwable $e) {
            log_message('error', 'Superadmin dashboard failed: {message}', ['message' => $e->getMessage()]);

            $weeklyLabels = [];
            $weeklyData = [];
            $weeklyUnityData = [];
            for ($i = 6; $i >= 0; $i--) {
                $weeklyLabels[] = date('D', strtotime("-{$i} days"));
                $weeklyData[] = 0;
                $weeklyUnityData[] = 0;
            }

            return view('superadmin/dashboard', [
                'total_users'    => 0,
                'user_online'    => 0,
                'absen_hari_ini' => 0,
                'absen_unity_hari_ini' => 0,
                'absen_dobel'    => 0,
                'absen_dobel_unity' => 0,
                'total_murid'    => 0,
                'weeklyLabels'   => $weeklyLabels,
                'weeklyData'     => $weeklyData,
                'weeklyUnityData'=> $weeklyUnityData,
                'roleLabels'     => ['Superadmin', 'Admin', 'Guru'],
                'roleData'       => [0, 0, 0],
                'activity'       => [],
                'maintenanceActive' => false,
                'unitySummary'   => [],
            ]);
        }
    }

    public function action()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'error']);
        }

        $type = $this->request->getPost('type');
        if (! $type) {
            $payload = $this->request->getJSON(true);
            $type = $payload['type'] ?? null;
        }
        $db   = Database::connect();

        if ($type === 'logout-all') {

            $db->table('users')->update([
                'session_token' => null
            ]);

            logSuperadmin(
                'force_logout',
                'Superadmin memaksa logout semua user'
            );

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Semua user berhasil dipaksa logout',
                'csrf'    => [
                    'name' => csrf_token(),
                    'hash' => csrf_hash(),
                ],
            ]);
        }

        if ($type === 'maintenance') {
            $row = $db->table('system_settings')
                ->where('setting_key', 'maintenance_mode')
                ->get()
                ->getRowArray();

            $current = (string) ($row['value'] ?? ($row['setting_value'] ?? '0'));
            $next = $current === '1' ? 0 : 1;

            if ($row) {
                $db->table('system_settings')
                    ->where('setting_key', 'maintenance_mode')
                    ->update([
                        'value' => $next,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            } else {
                $db->table('system_settings')->insert([
                    'setting_key'  => 'maintenance_mode',
                    'value'        => $next,
                    'description'  => 'Maintenance mode',
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
            }

            logSuperadmin(
                $next === 1 ? 'maintenance_on' : 'maintenance_off',
                $next === 1 ? 'Maintenance mode diaktifkan' : 'Maintenance mode dinonaktifkan'
            );

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => $next === 1 ? 'Maintenance mode aktif' : 'Maintenance mode nonaktif',
                'maintenance_active' => $next === 1,
                'csrf'    => [
                    'name' => csrf_token(),
                    'hash' => csrf_hash(),
                ],
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => 'Aksi tidak dikenal',
            'csrf'    => [
                'name' => csrf_token(),
                'hash' => csrf_hash(),
            ],
        ]);
    }

}
