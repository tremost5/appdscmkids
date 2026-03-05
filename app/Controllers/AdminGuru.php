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
    if (!$this->request->isAJAX()) {
        return $this->response->setStatusCode(403);
    }

    helper(['wa', 'wa_template']);

    $user = $this->userModel->find($id);
    if (!$user || $user['role_id'] != 3) {
        return $this->response->setJSON(['status' => 'error']);
    }

    $statusLama = $user['status'];
    $statusBaru = ($statusLama === 'aktif') ? 'nonaktif' : 'aktif';

    $this->userModel->update($id, [
        'status' => $statusBaru,
        'session_token' => $statusBaru === 'nonaktif' ? null : $user['session_token'],
        'last_activity' => $statusBaru === 'nonaktif' ? null : $user['last_activity'],
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
        kirimWA($targetNo, $rendered);
    }

    $adminNotice = "[NOTIF STATUS GURU]\n" . $rendered;
    foreach (waRecipientNumbers([1, 2], true) as $recipientNo) {
        if ($recipientNo === $targetNo) {
            continue;
        }
        kirimWA($recipientNo, $adminNotice);
    }

    return $this->response->setJSON([
        'status' => $statusBaru
    ]);
}

    // ===============================
    // UBAH ROLE GURU ↔ ADMIN
    // ===============================
    public function toggleRole($id)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->back()->with('error', 'User tidak ditemukan');
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
        $guru = $this->userModel->find($id);
        if (!$guru) {
            return redirect()->back()->with('error', 'Data guru tidak ditemukan');
        }

        $this->userModel->delete($id);

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


