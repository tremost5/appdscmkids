<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class AdminGuru extends BaseController
{
    protected $userModel;
    protected $db;

    public function __construct(){
        parent::__construct();
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
    }

    // ===============================
    // LIST GURU
    // ===============================
    public function index()
    {
        $guru = $this->userModel
            ->where('role_id', 3)
            ->orderBy('nama_depan', 'ASC')
            ->findAll();

        return view('admin/guru/index', [
            'guru' => $guru
        ]);
    }

    // ===============================
    // FORM TAMBAH GURU
    // ===============================
    public function create()
    {
        return view('admin/guru/create');
    }

    // ===============================
    // SIMPAN GURU
    // ===============================
    public function store()
    {
        helper('wa');

        $rules = [
            'nama_depan'    => 'required',
            'nama_belakang' => 'required',
            'username'      => 'required|is_unique[users.username]',
            'email'         => 'required|valid_email|is_unique[users.email]',
            'password'      => 'required|min_length[6]',
            'no_hp'         => 'required',
        ];

        if (! $this->validate($rules)) {
            $rawErrors = $this->validator->getErrors();
            $errors = [];
            array_walk_recursive($rawErrors, static function ($msg) use (&$errors): void {
                $errors[] = (string) $msg;
            });

            return redirect()->back()
                ->withInput()
                ->with('error', implode(' | ', $errors));
        }

        $noHp = function_exists('formatWA')
            ? formatWA($this->request->getPost('no_hp'))
            : preg_replace('/[^0-9]/', '', (string) $this->request->getPost('no_hp'));

        if (! $noHp) {
            return redirect()->back()->withInput()->with('error', 'Nomor WhatsApp tidak valid');
        }

        $data = [
            'nama_depan'    => $this->request->getPost('nama_depan'),
            'nama_belakang' => $this->request->getPost('nama_belakang'),
            'username'      => $this->request->getPost('username'),
            'email'         => $this->request->getPost('email'),
            'password'      => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'role_id'       => 3,
            'status'        => 'aktif',
            'no_hp'         => $noHp,
            'alamat'        => (string) $this->request->getPost('alamat'),
            'tanggal_lahir' => $this->request->getPost('tanggal_lahir') ?: date('Y-m-d'),
            'foto'          => 'default.png',
        ];

        $foto = $this->request->getFile('foto');
        if ($foto && $foto->isValid() && ! $foto->hasMoved()) {
            $path = FCPATH . 'uploads/guru/';
            if (! is_dir($path)) {
                mkdir($path, 0775, true);
            }
            $file = $foto->getRandomName();
            $foto->move($path, $file);
            $data['foto'] = $file;
        }

        $this->userModel->insert($data);

        $base = session('role_id') == 1 ? 'dashboard/superadmin/guru' : 'admin/guru';
        return redirect()->to(base_url($base))
            ->with('success', 'Guru berhasil ditambahkan');
    }

    // ===============================
    // TOGGLE STATUS (AJAX)
    // ===============================
    public function toggle($id)
{
    if (!$this->request->isAJAX() || strtoupper((string) $this->request->getMethod()) !== 'POST') {
        return $this->response->setStatusCode(403);
    }

    helper(['wa', 'wa_template']);

    $id = (int) $id;
    $user = $this->userModel->find($id);
    if (!$user || $user['role_id'] != 3) {
        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Guru tidak ditemukan',
        ])->setStatusCode(404);
    }

    $statusLama = $user['status'];
    $statusBaru = ($statusLama === 'aktif') ? 'nonaktif' : 'aktif';

    $this->db->transStart();
    $this->userModel->update($id, [
        'status' => $statusBaru,
        'session_token' => $statusBaru === 'nonaktif' ? null : $user['session_token'],
        'last_activity' => $statusBaru === 'nonaktif' ? null : $user['last_activity'],
    ]);
    $this->db->transComplete();

    if ($this->db->transStatus() === false) {
        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Gagal mengubah status guru',
        ])->setStatusCode(500);
    }

    logAudit('toggle_status_guru', 'warning', [
        'target' => 'users',
        'target_id' => $id,
        'old' => ['status' => $statusLama],
        'new' => ['status' => $statusBaru],
    ]);

    $namaDepan = (string) ($user['nama_depan'] ?? '');
    $namaBelakang = (string) ($user['nama_belakang'] ?? '');
    $namaLengkap = trim($namaDepan . ' ' . $namaBelakang);
    $username = (string) ($user['username'] ?? '');
    $targetNo = (string) ($user['no_hp'] ?? '');

    $vars = [
        'nama_lengkap' => $namaLengkap,
        'nama_depan' => $namaDepan,
        'nama_belakang' => $namaBelakang,
        'username' => $username,
        'no_hp' => $targetNo,
        'status' => $statusBaru,
    ];

    $templateKey = $statusBaru === 'aktif' ? 'guru_status_active' : 'guru_status_inactive';
    $rendered = waTemplateRender(waTemplateGet($templateKey), $vars);

    if ($targetNo !== '') {
        try {
            kirimWA($targetNo, $rendered);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal kirim WA status guru ke user {id}: {msg}', [
                'id' => $id,
                'msg' => $e->getMessage(),
            ]);
        }
    }

    $adminNotice = "[NOTIF STATUS GURU]\n" . $rendered;
    foreach (waRecipientNumbers([1, 2], true) as $recipientNo) {
        if ($recipientNo === $targetNo) {
            continue;
        }
        try {
            kirimWA($recipientNo, $adminNotice);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal kirim WA status guru ke admin recipient: {msg}', [
                'msg' => $e->getMessage(),
            ]);
        }
    }

    return $this->response->setJSON([
        'status' => $statusBaru,
        'message' => 'Status guru diperbarui',
        'csrf'   => [
            'name' => csrf_token(),
            'hash' => csrf_hash(),
        ],
    ]);
}

    // ===============================
    // UBAH ROLE GURU ↔ ADMIN
    // ===============================
    public function toggleRole($id)
    {
        if (strtoupper((string) $this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405);
        }

        $id = (int) $id;
        $actorId = (int) session()->get('user_id');
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->back()->with('error', 'User tidak ditemukan');
        }
        if ((int) ($user['role_id'] ?? 0) === 1) {
            return redirect()->back()->with('error', 'Role superadmin tidak boleh diubah dari menu ini.');
        }
        if ($actorId > 0 && $actorId === $id) {
            return redirect()->back()->with('error', 'Tidak boleh mengubah role akun sendiri.');
        }
        if (!in_array((int) ($user['role_id'] ?? 0), [2, 3], true)) {
            return redirect()->back()->with('error', 'Role user ini tidak didukung untuk toggle.');
        }

        $roleBaru = ($user['role_id'] == 3) ? 2 : 3;

        $this->db->transStart();

        // UPDATE ROLE
        $this->userModel->update($id, [
            'role_id' => $roleBaru
        ]);

        // ===============================
        // AUTO SYNC WA ADMIN
        // ===============================
        if (!empty($user['no_hp'])) {
            $exists = $this->db->table('wa_recipients')
                ->where('no_hp', $user['no_hp'])
                ->get()
                ->getRowArray();

            if ($roleBaru == 2) {

    // ===== AKTIFKAN WA ADMIN =====
    if ($exists) {
        $this->db->table('wa_recipients')
            ->where('no_hp', $user['no_hp'])
            ->update([
                'user_id'   => $id,
                'role_id'   => 2,
                'is_active' => 1
            ]);
    } else {
        $this->db->table('wa_recipients')->insert([
            'user_id'    => $id,
            'no_hp'      => $user['no_hp'],
            'role_id'    => 2,
            'is_active'  => 1
        ]);
    }

    // ===== NOTIF WA KE USER =====
    if (!empty($user['no_hp'])) {

        $pesan = "📢 *Pemberitahuan Sistem*\n\n"
               . "Shalom {$user['nama_depan']} 👋\n\n"
               . "Saat ini akun Anda telah *DITINGKATKAN menjadi ADMIN*.\n\n"
               . "Silakan logout lalu login kembali untuk mengakses menu admin.\n\n"
               . "Tuhan Yesus Memberkati 🙏";

        // ⬇️ GANTI SESUAI FUNGSI WA KAMU
        if (function_exists('kirimWA')) {
            kirimWA($user['no_hp'], $pesan);
        }
    }
} else {
    // kembali jadi guru: nonaktifkan penerima WA admin
    $this->db->table('wa_recipients')
        ->where('user_id', $id)
        ->update([
            'role_id'   => 3,
            'is_active' => 0
        ]);
}
}

        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal mengubah role user.');
        }

        logAudit('toggle_role_user', 'warning', [
            'target' => 'users',
            'target_id' => $id,
            'old' => ['role_id' => (int) ($user['role_id'] ?? 0)],
            'new' => ['role_id' => $roleBaru],
        ]);

        return redirect()->back()->with(
            'success',
            $roleBaru == 2
                ? 'Guru berhasil dijadikan Admin (WA aktif)'
                : 'Admin dikembalikan menjadi Guru (WA nonaktif)'
        );
    }

    // ===============================
    // DELETE
    // ===============================
    public function delete($id)
    {
        if (strtoupper((string) $this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405);
        }

        $id = (int) $id;
        $actorId = (int) session()->get('user_id');
        $guru = $this->userModel->find($id);
        if (!$guru) {
            return redirect()->back()->with('error', 'Data guru tidak ditemukan');
        }
        if ((int) ($guru['role_id'] ?? 0) !== 3) {
            return redirect()->back()->with('error', 'Hanya akun guru yang bisa dihapus dari menu ini.');
        }
        if ($actorId > 0 && $actorId === $id) {
            return redirect()->back()->with('error', 'Tidak boleh menghapus akun sendiri.');
        }

        $hasAbsensi = $this->db->table('absensi')->where('guru_id', $id)->countAllResults() > 0;
        $hasAudit = $this->db->table('audit_log')->where('user_id', $id)->countAllResults() > 0;
        $hasKegiatan = $this->db->table('guru_kegiatan')->where('guru_id', $id)->countAllResults() > 0;

        if ($hasAbsensi || $hasAudit || $hasKegiatan) {
            return redirect()->back()->with(
                'error',
                'Guru sudah memiliki histori data. Gunakan nonaktifkan, jangan hapus permanen.'
            );
        }

        $this->db->transStart();
        $this->db->table('wa_recipients')->where('user_id', $id)->delete();
        $this->userModel->delete($id);
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal menghapus guru.');
        }

        $foto = trim((string) ($guru['foto'] ?? ''));
        if ($foto !== '' && $foto !== 'default.png') {
            $fotoPath = FCPATH . 'uploads/guru/' . basename($foto);
            if (is_file($fotoPath)) {
                @unlink($fotoPath);
            }
        }

        logAudit('delete_guru', 'warning', [
            'target' => 'users',
            'target_id' => $id,
            'old' => [
                'nama_depan' => $guru['nama_depan'] ?? null,
                'nama_belakang' => $guru['nama_belakang'] ?? null,
                'username' => $guru['username'] ?? null,
                'email' => $guru['email'] ?? null,
                'status' => $guru['status'] ?? null,
            ],
            'new' => ['deleted' => true],
        ]);

        return redirect()->back()->with('success', 'Guru berhasil dihapus');
    }

    // ===============================
    // DETAIL (AJAX)
    // ===============================
    public function detail($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $guru = $this->userModel
            ->select('id, nama_depan, nama_belakang, username, alamat, no_hp, foto')
            ->where('id', $id)
            ->where('role_id', 3)
            ->first();

        if (!$guru) {
            return $this->response->setJSON(['status' => 'error']);
        }

        return $this->response->setJSON([
            'status' => 'ok',
            'data'   => $guru
        ]);
    }

    public function notifCount()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }
        try {
            $startToday = date('Y-m-d 00:00:00');
            $fields = array_map('strtolower', $this->db->getFieldNames('users'));
            $hasCreatedAt = in_array('created_at', $fields, true);

            $nonaktif = (int) $this->db->table('users')
                ->where('role_id', 3)
                ->where('status', 'nonaktif')
                ->countAllResults();

            $baruHariIni = 0;
            if ($hasCreatedAt) {
                $baruHariIni = (int) $this->db->table('users')
                    ->where('role_id', 3)
                    ->where('created_at', '>=', $startToday)
                    ->countAllResults();
            }

            $totalAlert = $nonaktif;
            if ($hasCreatedAt) {
                $totalAlert = (int) $this->db->table('users')
                    ->where('role_id', 3)
                    ->groupStart()
                        ->where('status', 'nonaktif')
                        ->orWhere('created_at', '>=', $startToday)
                    ->groupEnd()
                    ->countAllResults();
            }

            return $this->response->setJSON([
                'nonaktif' => $nonaktif,
                'baru_hari_ini' => $baruHariIni,
                'total_alert' => $totalAlert,
                'has_alert' => $totalAlert > 0,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'nonaktif' => 0,
                'baru_hari_ini' => 0,
                'total_alert' => 0,
                'has_alert' => false,
                'error' => true,
            ]);
        }
    }
}


