<?php

namespace App\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\UserModel;

class Auth extends BaseController

{
    public function login()
    {
        $a = rand(1, 9);
        $b = rand(1, 9);

        session()->set([
            'captcha_ans' => $a + $b,
            'captcha_q'   => "$a + $b = ?"
        ]);

        return view('auth/login');
    }

    public function attemptLogin()
    {
        helper('audit');
        app(LoginRequest::class)->validated();

        // ==========================
        // CAPTCHA CHECK
        // ==========================
        if ((int)$this->request->getPost('captcha') !== session()->get('captcha_ans')) {
            return redirect()->back()->with('error', 'Captcha salah');
        }

        $model = new UserModel();

        // ==========================
        // AMBIL USER
        // ==========================
        $user = $model
            ->where('username', $this->request->getPost('username'))
            ->where('status', 'aktif')
            ->first();

        if (!$user) {
            return redirect()->back()->with('error', 'Username tidak ditemukan atau belum aktif');
        }

        // ==========================
        // CEK PASSWORD
        // ==========================
        if (!password_verify($this->request->getPost('password'), $user['password'])) {
            return redirect()->back()->with('error', 'Password salah');
        }

        // ==========================
        // REGENERATE SESSION
        // ==========================
        session()->regenerate(true);
        $sessionToken = bin2hex(random_bytes(32));

        // ==========================
        // UPDATE LOGIN INFO
        // ==========================
        $model->update($user['id'], [
            'last_login' => date('Y-m-d H:i:s'),
            'last_seen'  => date('Y-m-d H:i:s'),
            'session_token' => $sessionToken,
        ]);

        // ===== SET SESSION LOGIN =====
session()->set([
    'user_id'       => $user['id'],
    'nama_depan'    => $user['nama_depan'],
    'nama_belakang' => $user['nama_belakang'],
    'email'         => $user['email'],
    'role_id'       => $user['role_id'],
    'kelas_id'      => $user['kelas_id'] ?? null,
    'foto'          => $user['foto'] ?? 'default.png',
    'isLoggedIn'    => true,
    'last_login'    => $user['last_login'],
    'session_token' => $sessionToken,

]);


        // ==========================
        // AUDIT LOG
        // ==========================
        logAudit('login', 'info', [
            'user_id' => $user['id'],
            'new' => ['keterangan' => 'User login ke sistem'],
        ]);

        // ==========================
        // REDIRECT SESUAI ROLE
        // ==========================
        if ($user['role_id'] == 1) {
            return redirect()->to('/dashboard/superadmin');
        }

        if ($user['role_id'] == 2) {
            return redirect()->to('/dashboard/admin');
        }

        if ($user['role_id'] == 3) {
            return redirect()->to('/dashboard/guru');
        }

        // fallback (harusnya ga kepakai)
        return redirect()->to('/logout');
    }

    public function logout()
    {
        helper('audit');
        $userId = (int) session()->get('user_id');

        if (session()->get('user_id')) {
            logAudit('logout', 'info', [
                'user_id' => $userId,
                'new' => ['keterangan' => 'User logout'],
            ]);
        }

        if ($userId > 0) {
            (new UserModel())->update($userId, ['session_token' => null]);
        }

        session()->destroy();
        return redirect()->to('/login');
    }
}
