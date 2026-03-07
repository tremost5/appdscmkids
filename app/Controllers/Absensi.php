<?php

namespace App\Controllers;

use Config\Database;

class Absensi extends BaseController
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::connect();
    }

    public function step1()
    {
        $lokasiOptions = $this->fetchLokasiOptions(false);

        return view('guru/absensi_step1', [
            'kelasGroups' => kelasGroupMap(),
            'lokasiOptions' => $lokasiOptions,
            'currentTime' => date('H:i:s'),
        ]);
    }

    public function unityStep1()
    {
        $lokasiOptions = $this->fetchLokasiOptions(true);

        return view('guru/unity_step1', [
            'kelasGroups' => kelasGroupMap(),
            'unityMap' => unityMetaMap(),
            'lokasiOptions' => $lokasiOptions,
            'currentTime' => date('H:i:s'),
        ]);
    }

    public function tampilkan()
    {
        $kelasGroups = (array) $this->request->getGet('kelas');
        $lokasiId = (int) $this->request->getGet('lokasi');
        $jamPreset = $this->normalizeClockInput((string) $this->request->getGet('jam_input'));
        $lokasi = $this->resolveLocationSelection($lokasiId, '', 'reguler');

        if (empty($kelasGroups) || $lokasi === null) {
            return redirect()->to('guru/absensi')->with('error', 'Kelas dan lokasi wajib dipilih.');
        }

        $resolved = $this->resolveClassIdsByGroupKeys($kelasGroups);
        if (empty($resolved['classIds'])) {
            return redirect()->to('guru/absensi')->with('error', 'Mapping kelas tidak ditemukan.');
        }

        $murid = $this->fetchMuridAktif($resolved['classIds'], null);

        return view('guru/absensi_step2', [
            'lokasi_id' => $lokasi['id'],
            'lokasi_text' => $lokasi['display'],
            'lokasi_custom' => $lokasi['note'] ?? '',
            'murid' => $murid,
            'kelasLabels' => $resolved['classLabels'],
            'saveAction' => base_url('guru/absensi/simpan'),
            'modeTitle' => 'FORM PRESENSI',
            'modeSubtitle' => 'Pastikan data benar sebelum menyimpan',
            'activeUnity' => '',
            'jamPreset' => $jamPreset,
        ]);
    }

    public function unityTampilkan()
    {
        $unity = trim((string) $this->request->getGet('unity'));
        $lokasiId = (int) $this->request->getGet('lokasi');
        $lokasiCustom = $this->sanitizeCustomLocation((string) $this->request->getGet('lokasi_lainnya'));
        $kelasGroups = (array) $this->request->getGet('kelas');
        $hasUnity = $this->hasTableColumn('murid', 'unity');
        $jamPreset = $this->normalizeClockInput((string) $this->request->getGet('jam_input'));
        $lokasi = $this->resolveLocationSelection($lokasiId, $lokasiCustom, 'unity');

        if (!$hasUnity || $unity === '' || !isset(unityMetaMap()[$unity]) || $lokasi === null) {
            return redirect()->to('guru/unity')->with('error', 'Unity dan lokasi wajib dipilih.');
        }

        $classIds = [];
        $classLabels = [];
        if (!empty($kelasGroups)) {
            $resolved = $this->resolveClassIdsByGroupKeys($kelasGroups);
            $classIds = $resolved['classIds'];
            $classLabels = $resolved['classLabels'];
        }

        $murid = $this->fetchMuridAktif($classIds, $unity);
        if (empty($murid)) {
            return redirect()->to('guru/unity')->with('error', 'Tidak ada murid aktif pada filter yang dipilih.');
        }

        if (empty($classLabels)) {
            $classLabels = $this->resolveClassLabelsFromRows($murid);
        }

        return view('guru/absensi_step2', [
            'lokasi_id' => $lokasi['id'],
            'lokasi_text' => $lokasi['display'],
            'lokasi_custom' => $lokasi['note'] ?? '',
            'murid' => $murid,
            'kelasLabels' => $classLabels,
            'saveAction' => base_url('guru/unity/simpan'),
            'modeTitle' => 'FORM PRESENSI UNITY',
            'modeSubtitle' => 'Presensi khusus murid berdasarkan Unity',
            'activeUnity' => $unity,
            'jamPreset' => $jamPreset,
        ]);
    }

    public function simpan()
    {
        return $this->doSimpan('reguler');
    }

    public function simpanUnity()
    {
        return $this->doSimpan('unity');
    }

    public function dobel()
    {
        return view('guru/absensi_dobel', [
            'dobel' => [],
            'kelasMap' => array_map(static fn ($row) => $row['label'], kelasMap()),
        ]);
    }

    private function doSimpan(string $jenisPresensi = 'reguler')
    {
        try {
            $jenisPresensi = in_array($jenisPresensi, ['reguler', 'unity'], true) ? $jenisPresensi : 'reguler';
            $tanggal = date('Y-m-d');
            $jam = $this->normalizeClockInput((string) ($this->request->getPost('jam_input') ?: $this->request->getPost('jam_preset')));
            $guruId = session('user_id');
            $guruNama = trim(session('nama_depan') . ' ' . session('nama_belakang'));
            $lokasiId = (int) $this->request->getPost('lokasi_id');
            $lokasiCustom = $this->sanitizeCustomLocation((string) $this->request->getPost('lokasi_custom'));
            $murids = $this->request->getPost('hadir') ?? [];

            if (!$murids) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Tidak ada murid dipilih',
                ]);
            }

            $selfie = $this->request->getFile('selfie');
            if (!$selfie || !$selfie->isValid()) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Selfie wajib',
                ]);
            }

            $path = FCPATH . 'uploads/selfie/';
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            $selfieName = 'selfie_' . $guruId . '_' . time() . '.jpg';
            if (!$selfie->move($path, $selfieName)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Gagal upload selfie',
                ]);
            }

            $this->db->transBegin();

            $lokasi = $this->resolveLocationSelection($lokasiId, $lokasiCustom, $jenisPresensi);
            if ($lokasi === null) {
                $this->db->transRollback();
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Lokasi tidak valid',
                ]);
            }

            $this->db->table('absensi')->insert([
                'guru_id' => $guruId,
                'lokasi_id' => $lokasi['id'],
                'lokasi_text' => $lokasi['text'],
                'keterangan' => $lokasi['note'],
                'jenis_presensi' => $jenisPresensi,
                'tanggal' => $tanggal,
                'jam' => $jam,
                'selfie_foto' => $selfieName,
            ]);

            $absensiId = $this->db->insertID();
            $dobel = [];

            foreach ($murids as $muridId) {
                $exist = $this->db->table('absensi_detail d')
                    ->join('absensi a', 'a.id = d.absensi_id')
                    ->where('d.murid_id', $muridId)
                    ->where('d.tanggal', $tanggal)
                    ->where('a.jenis_presensi', $jenisPresensi)
                    ->countAllResults() > 0;

                $status = $exist ? 'dobel' : 'hadir';

                $this->db->table('absensi_detail')->insert([
                    'absensi_id' => $absensiId,
                    'murid_id' => $muridId,
                    'status' => $status,
                    'tanggal' => $tanggal,
                ]);
                $absensiDetailId = (int) $this->db->insertID();

                $this->db->table('absensi_log')->insert([
                    'absensi_detail_id' => $absensiDetailId,
                    'murid_id' => (int) $muridId,
                    'aksi' => 'create',
                    'status_baru' => $status,
                    'oleh' => 'guru',
                    'user_id' => $guruId,
                ]);

                if ($status === 'dobel') {
                    $unitySelect = $this->hasTableColumn('murid', 'unity') ? 'm.unity,' : "'' AS unity,";
                    $first = $this->db->table('absensi_detail d')
                        ->select("
                            m.nama_depan,
                            m.nama_belakang,
                            k.nama_kelas,
                            {$unitySelect}
                            a.jenis_presensi,
                            a.keterangan,
                            COALESCE(NULLIF(a.keterangan, ''), a.lokasi_text) AS lokasi_text,
                            a.jam,
                            CONCAT(u.nama_depan,' ',u.nama_belakang) AS guru_pertama
                        ")
                        ->join('murid m', 'm.id=d.murid_id')
                        ->join('kelas k', 'k.id=m.kelas_id')
                        ->join('absensi a', 'a.id=d.absensi_id')
                        ->join('users u', 'u.id=a.guru_id')
                        ->where('d.murid_id', $muridId)
                        ->where('d.tanggal', $tanggal)
                        ->where('a.jenis_presensi', $jenisPresensi)
                        ->orderBy('a.jam', 'ASC')
                        ->get()->getRowArray();

                    $dobel[] = [
                        'murid_id' => $muridId,
                        'detail' => $first ? array_merge($first, [
                            'lokasi_text' => formatLokasiDisplay($first['lokasi_text'] ?? '', $first['keterangan'] ?? null),
                        ]) : $first,
                    ];
                }
            }

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                throw new \Exception('DB error');
            }

            $this->db->transCommit();

            return $this->response->setJSON([
                'status' => empty($dobel) ? 'success' : 'duplicate',
                'tanggal' => $tanggal,
                'guru' => $guruNama,
                'dobel' => $dobel,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function hariIni()
    {
        return $this->renderHariIni('reguler', 'guru/absensi-hari-ini', 'guru/absensi-hari-ini/simpan', 'guru/absensi');
    }

    public function hariIniUnity()
    {
        return $this->renderHariIni('unity', 'guru/unity-hari-ini', 'guru/unity-hari-ini/simpan', 'guru/unity');
    }

    public function simpanEditHariIni()
    {
        return $this->handleSimpanEditHariIni('reguler', 'guru/absensi-hari-ini');
    }

    public function simpanEditHariIniUnity()
    {
        return $this->handleSimpanEditHariIni('unity', 'guru/unity-hari-ini');
    }

    private function renderHariIni(string $jenisPresensi, string $dayUrl, string $saveUrl, string $backUrl)
    {
        $tanggal = date('Y-m-d');
        $guruId = session('user_id');

        $absensi = $this->db->table('absensi')
            ->where('guru_id', $guruId)
            ->where('tanggal', $tanggal)
            ->where('jenis_presensi', $jenisPresensi)
            ->orderBy('jam', 'DESC')
            ->get()
            ->getRowArray();

        if (!$absensi) {
            return view('guru/absensi_hari_ini', [
                'absensi' => null,
                'detail' => [],
                'modeLabel' => ucfirst($jenisPresensi),
                'saveUrl' => base_url($saveUrl),
                'backUrl' => base_url($backUrl),
                'dayUrl' => base_url($dayUrl),
            ]);
        }

        $unitySelect = $this->hasTableColumn('murid', 'unity') ? 'm.unity' : "'' AS unity";
        $detail = $this->db->table('absensi_detail d')
            ->select('
                d.murid_id,
                d.status,
                m.nama_depan,
                m.nama_belakang,
                m.panggilan,
                m.kelas_id,
                '.$unitySelect.',
                k.nama_kelas,
                m.foto
            ')
            ->join('murid m', 'm.id = d.murid_id')
            ->join('kelas k', 'k.id = m.kelas_id')
            ->where('d.absensi_id', $absensi['id'])
            ->orderBy('m.kelas_id', 'ASC')
            ->orderBy('m.nama_depan', 'ASC')
            ->get()
            ->getResultArray();

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
            'detail' => $detail,
            'hadir' => $hadir,
            'dobel' => $dobel,
            'modeLabel' => ucfirst($jenisPresensi),
            'saveUrl' => base_url($saveUrl),
            'backUrl' => base_url($backUrl),
            'dayUrl' => base_url($dayUrl),
        ]);
    }

    private function handleSimpanEditHariIni(string $jenisPresensi, string $redirectPath)
    {
        $absensiId = (int) $this->request->getPost('absensi_id');
        $hadirRaw = $this->request->getPost('hadir');
        $hadirIds = array_values(array_unique(array_filter(
            array_map('intval', (array) $hadirRaw),
            static fn ($v) => $v > 0
        )));
        $userId = (int) session('user_id');

        if (!$absensiId) {
            return redirect()->back()->with('error', 'Absensi tidak valid');
        }

        $owner = $this->db->table('absensi')
            ->select('id')
            ->where('id', $absensiId)
            ->where('guru_id', $userId)
            ->where('jenis_presensi', $jenisPresensi)
            ->get()
            ->getRowArray();

        if (!$owner) {
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

        if (!empty($hadirIds)) {
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
                fn ($b) => $b['murid_id'] == $row['murid_id']
            );

            $oldStatus = $old ? array_values($old)[0]['status'] : null;

            if ($oldStatus !== $row['status']) {
                $changed++;
                logAudit(
                    'update_absensi',
                    'info',
                    [
                        'murid_id' => $row['murid_id'],
                        'absensi_id' => $absensiId,
                        'old' => ['status' => $oldStatus],
                        'new' => ['status' => $row['status']],
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
                ->to(base_url($redirectPath))
                ->with('error', 'Tidak ada perubahan status yang tersimpan');
        }

        return redirect()
            ->to(base_url($redirectPath))
            ->with('success', 'Perubahan absensi berhasil disimpan');
    }

    private function fetchMuridAktif(array $classIds, ?string $unity): array
    {
        $hasUnity = $this->hasTableColumn('murid', 'unity');
        $builder = $this->db->table('murid')
            ->select('id, nama_depan, nama_belakang, panggilan, kelas_id, status, foto'.($hasUnity ? ', unity' : ", '' AS unity"))
            ->where('status', 'aktif');

        if (!empty($classIds)) {
            $builder->whereIn('kelas_id', $classIds);
        }

        if ($hasUnity && !empty($unity)) {
            $builder->where('unity', $unity);
        }

        return $builder
            ->orderBy('kelas_id', 'ASC')
            ->orderBy('nama_depan', 'ASC')
            ->get()->getResultArray();
    }

    private function resolveClassIdsByGroupKeys(array $groupKeys): array
    {
        $groups = kelasGroupMap();
        $groupKeys = array_values(array_unique(array_filter(array_map('strval', $groupKeys))));

        $targetCodes = [];
        foreach ($groupKeys as $key) {
            if (!isset($groups[$key])) {
                continue;
            }
            foreach ($groups[$key]['codes'] as $code) {
                $targetCodes[] = $code;
            }
        }
        $targetCodes = array_values(array_unique($targetCodes));
        if (empty($targetCodes)) {
            return ['classIds' => [], 'classLabels' => []];
        }

        $rows = $this->db->table('kelas')
            ->select('id, kode_kelas, nama_kelas')
            ->whereIn('kode_kelas', $targetCodes)
            ->get()->getResultArray();

        $classIds = array_map(static fn ($r) => (int) $r['id'], $rows);
        $classLabels = [];
        foreach ($rows as $row) {
            $classLabels[(int) $row['id']] = (string) ($row['kode_kelas'] ?: $row['nama_kelas']);
        }

        return [
            'classIds' => $classIds,
            'classLabels' => $classLabels,
        ];
    }

    private function resolveClassLabelsFromRows(array $muridRows): array
    {
        $ids = array_values(array_unique(array_map(
            static fn ($r) => (int) ($r['kelas_id'] ?? 0),
            $muridRows
        )));
        $ids = array_values(array_filter($ids, static fn ($v) => $v > 0));
        if (empty($ids)) {
            return [];
        }

        $rows = $this->db->table('kelas')
            ->select('id, kode_kelas, nama_kelas')
            ->whereIn('id', $ids)
            ->get()->getResultArray();

        $labels = [];
        foreach ($rows as $row) {
            $labels[(int) $row['id']] = (string) ($row['kode_kelas'] ?: $row['nama_kelas']);
        }

        return $labels;
    }

    private function sanitizeCustomLocation(string $value): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        return trim($value);
    }

    private function normalizeClockInput(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
            return $value . ':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
            return $value;
        }

        return date('H:i:s');
    }

    private function fetchLokasiOptions(bool $allowLainnya): array
    {
        $allowedNames = $allowLainnya
            ? ['NICC', 'GRASA', 'CPM', 'Lokasi Lainnya']
            : ['NICC', 'GRASA', 'CPM'];

        return $this->db->table('lokasi_ibadah')
            ->select('id, nama_lokasi')
            ->whereIn('nama_lokasi', $allowedNames)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function resolveLocationSelection(int $lokasiId, string $lokasiCustom, string $jenisPresensi): ?array
    {
        if ($lokasiId <= 0) {
            return null;
        }

        $row = $this->db->table('lokasi_ibadah')
            ->select('id, nama_lokasi')
            ->where('id', $lokasiId)
            ->get()
            ->getRowArray();

        if (!$row) {
            return null;
        }

        $name = (string) $row['nama_lokasi'];
        if ($jenisPresensi === 'reguler' && !in_array($name, ['NICC', 'GRASA', 'CPM'], true)) {
            return null;
        }

        if ($jenisPresensi === 'unity') {
            $isLainnya = $name === 'Lokasi Lainnya';
            if ($isLainnya && $lokasiCustom === '') {
                return null;
            }

            return [
                'id' => (int) $row['id'],
                'text' => $name,
                'note' => $isLainnya ? $lokasiCustom : null,
                'display' => formatLokasiDisplay($name, $isLainnya ? $lokasiCustom : null),
                'is_custom' => $isLainnya,
            ];
        }

        return [
            'id' => (int) $row['id'],
            'text' => $name,
            'note' => null,
            'display' => formatLokasiDisplay($name),
            'is_custom' => false,
        ];
    }
}
