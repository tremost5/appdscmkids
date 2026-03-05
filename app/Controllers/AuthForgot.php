<?php

namespace App\Controllers;

use App\Models\UserModel;
use Illuminate\Support\Facades\Mail;

class AuthForgot extends BaseController
{
    public function index()
    {
        return view('auth/forgot_choice');
    }

    // =========================
    // RESET VIA EMAIL
    // =========================
    public function email()
    {
        $email = trim((string) $this->request->getPost('email'));

        if (!$email) {
            return redirect()->back()->with('error', 'Email wajib diisi.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('error', 'Format email tidak valid.');
        }

        $model = new UserModel();
        $user  = $model->where('email', $email)->first();

        if (!$user) {
            return redirect()->back()
                ->with('error', 'Email tidak terdaftar.');
        }

        $token = bin2hex(random_bytes(32));
        $expiredAt = date('Y-m-d H:i:s', strtotime('+60 minutes'));

        $model->update($user['id'], [
            'reset_token' => $token,
            'reset_expires' => $expiredAt,
        ]);

        $resetLink = base_url('reset-password-email?token=' . urlencode($token));
        $nama = trim((string) (($user['nama_depan'] ?? '') . ' ' . ($user['nama_belakang'] ?? '')));
        $nama = $nama !== '' ? $nama : (string) ($user['username'] ?? 'User');

        $mailBody = "Shalom {$nama},\n\n"
            . "Kami menerima permintaan reset password akun Presensi Anda.\n"
            . "Klik link berikut untuk membuat password baru:\n{$resetLink}\n\n"
            . "Link berlaku sampai {$expiredAt}.\n"
            . "Jika Anda tidak merasa meminta reset password, abaikan email ini.\n\n"
            . "Tuhan Yesus memberkati.";

        try {
            Mail::raw($mailBody, function ($message) use ($email, $nama): void {
                $message->to($email, $nama)
                    ->subject('Reset Password - DSCMKIDS APP');
            });
        } catch (\Throwable $e) {
            log_message('error', 'Gagal kirim email reset password: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengirim email reset password. Coba lagi nanti.');
        }

        return redirect()->back()
            ->with('success', 'Link reset password sudah dikirim ke email Anda.');
    }

    public function resetFormEmail()
    {
        $token = trim((string) $this->request->getGet('token'));
        if ($token === '') {
            return redirect()->to('/forgot')->with('error', 'Token reset tidak valid.');
        }

        $model = new UserModel();
        $user = $model
            ->where('reset_token', $token)
            ->where('reset_expires >=', date('Y-m-d H:i:s'))
            ->first();

        if (!$user) {
            return redirect()->to('/forgot')->with('error', 'Link reset tidak valid atau sudah kedaluwarsa.');
        }

        return view('auth/reset_password_email', [
            'token' => $token,
            'email' => (string) ($user['email'] ?? ''),
        ]);
    }

    public function resetSaveEmail()
    {
        $token = trim((string) $this->request->getPost('token'));
        $password = (string) $this->request->getPost('password');
        $confirm = (string) $this->request->getPost('password_confirm');

        if ($token === '') {
            return redirect()->to('/forgot')->with('error', 'Token reset tidak valid.');
        }

        if ($password === '' || $confirm === '') {
            return redirect()->back()->withInput()->with('error', 'Password wajib diisi.');
        }

        if (strlen($password) < 6) {
            return redirect()->back()->withInput()->with('error', 'Password minimal 6 karakter.');
        }

        if ($password !== $confirm) {
            return redirect()->back()->withInput()->with('error', 'Konfirmasi password tidak cocok.');
        }

        $model = new UserModel();
        $user = $model
            ->where('reset_token', $token)
            ->where('reset_expires >=', date('Y-m-d H:i:s'))
            ->first();

        if (!$user) {
            return redirect()->to('/forgot')->with('error', 'Link reset tidak valid atau sudah kedaluwarsa.');
        }

        $model->update((int) $user['id'], [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'reset_token' => null,
            'reset_expires' => null,
        ]);

        return redirect()->to('/login')->with('success', 'Password berhasil diperbarui. Silakan login.');
    }

    // =========================
    // RESET VIA WHATSAPP (OTP)
    // =========================
    public function wa()
{
    helper('wa');

    $input = $this->request->getPost('no_hp');
    $no_hp = formatWA((string) $input);

    if (!$no_hp) {
        return redirect()->back()
            ->with('error', 'Format nomor WhatsApp tidak valid.');
    }

    $model = new \App\Models\UserModel();
    $user  = $model->where('no_hp', $no_hp)->first();

    if (!$user) {
        return redirect()->back()
            ->with('error', 'Nomor WhatsApp tidak terdaftar.');
    }

    // OTP
    $otp = rand(100000, 999999);

    $model->update($user['id'], [
        'reset_token'   => $otp,
        'reset_expires' => date('Y-m-d H:i:s', strtotime('+5 minutes')),
    ]);

    $pesan =
        "🔐 *Reset Password*\n\n"
      . "Kode OTP Anda:\n"
      . "*{$otp}*\n\n"
      . "Berlaku 5 menit.\n"
      . "Jangan bagikan kode ini.";

    kirimWA($no_hp, $pesan);

    session()->set('reset_user', $user['id']);

    return redirect()->to('/verify-otp')
        ->with('success', 'Kode OTP telah dikirim ke WhatsApp Anda.');
}


}
