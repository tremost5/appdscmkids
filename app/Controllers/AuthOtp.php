<?php

namespace App\Controllers;

use App\Models\UserModel;

class AuthOtp extends BaseController
{
    // ===== FORM INPUT OTP =====
    public function form()
    {
        return view('auth/otp_verify');
    }

    // ===== CEK OTP =====
    public function verify()
{
    $otp = $this->request->getPost('otp');

    if (!$otp) {
        return redirect()->back()->with('error', 'Kode OTP wajib diisi.');
    }

    $model = new \App\Models\UserModel();
    $user = $model->where('reset_token', $otp)
                  ->where('reset_expires', '>=', date('Y-m-d H:i:s'))
                  ->first();

    if (!$user) {
        return redirect()->back()
            ->with('error', 'Kode OTP tidak valid atau sudah kedaluwarsa.');
    }

    session()->set('reset_user', $user['id']);

    return redirect()->to('/reset-password-wa');
}

    // ===== FORM RESET PASSWORD =====
    public function resetForm()
    {
        if (!session()->get('reset_user')) {
            return redirect()->to('/forgot');
        }

        return view('auth/reset_password_wa');
    }

    // ===== SIMPAN PASSWORD BARU =====
    public function resetSave()
{
    $userId = session()->get('reset_user');

    if (!$userId) {
        return redirect()->to('/login')
            ->with('error', 'Sesi reset telah berakhir. Silakan ulangi.');
    }

    $password = $this->request->getPost('password');
    $confirm  = $this->request->getPost('password_confirm');

    if (!$password || !$confirm) {
        return redirect()->back()->with('error', 'Password wajib diisi.');
    }

    if ($password !== $confirm) {
        return redirect()->back()->with('error', 'Konfirmasi password tidak cocok.');
    }

    $model = new \App\Models\UserModel();

    // === UPDATE PASSWORD ===
    $model->update($userId, [
        'password'     => password_hash($password, PASSWORD_DEFAULT),
        'reset_token'  => null,
        'reset_expires'=> null
    ]);

    // === AMBIL DATA USER ===
    $user = $model->find($userId);

    // === AUTO LOGIN ===
    session()->remove('reset_user');
    session()->set([
        'user_id'   => $user['id'],
        'username'  => $user['username'],
        'role_id'   => $user['role_id'],
        'isLoggedIn'=> true
    ]);

    // === OPTIONAL: NOTIF WA PASSWORD BERHASIL DIGANTI ===
    $pesan =
        "🔐 *Password Berhasil Diubah*\n\n"
      . "Shalom Bapak/Ibu 🙏\n"
      . "Password akun Anda telah berhasil diperbarui.\n\n"
      . "Username: {$user['username']}\n"
      . "Silakan langsung gunakan sistem.\n\n"
      . "Tuhan Yesus Memberkati 💙";

    kirimWA($user['no_hp'], $pesan);

    // === REDIRECT SESUAI ROLE ===
    if ($user['role_id'] == 1) {
        return redirect()->to('/dashboard/superadmin')
            ->with('success', 'Password berhasil diubah. Anda sudah login.');
    }

    if ($user['role_id'] == 2) {
        return redirect()->to('/dashboard/admin')
            ->with('success', 'Password berhasil diubah. Anda sudah login.');
    }

    return redirect()->to('/dashboard/guru')
        ->with('success', 'Password berhasil diubah. Anda sudah login.');
}


}
