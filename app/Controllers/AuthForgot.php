<?php

namespace App\Controllers;

use App\Models\UserModel;

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
        $email = $this->request->getPost('email');

        if (!$email) {
            return redirect()->back()->with('error', 'Email wajib diisi.');
        }

        $model = new UserModel();
        $user  = $model->where('email', $email)->first();

        if (!$user) {
            return redirect()->back()
                ->with('error', 'Email tidak terdaftar.');
        }

        // 👉 LOGIC KIRIM EMAIL PUNYAMU TETAP DI SINI

        return redirect()->back()
            ->with('success', 'Link reset password sudah dikirim ke email Anda.');
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
