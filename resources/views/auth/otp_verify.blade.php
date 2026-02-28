<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verifikasi OTP</title>
<link rel="icon" type="image/png" sizes="192x192" href="<?= base_url('pwa/icons/icon-192.png') ?>">
<link rel="apple-touch-icon" href="<?= base_url('pwa/icons/icon-192.png') ?>">

<link rel="stylesheet" href="<?= base_url('assets/adminlte/css/adminlte.min.css') ?>">

<style>
body {
  background: linear-gradient(135deg,#36d1dc,#5b86e5);
  min-height:100vh;
}
.card { border-radius:16px; }
.otp-input {
  font-size:32px;
  text-align:center;
  letter-spacing:12px;
}
</style>
</head>

<body class="d-flex align-items-center justify-content-center">

<div class="col-11 col-md-4">

<?php foreach (['success','error'] as $t): ?>
<?php if (session()->getFlashdata($t)): ?>
<div class="alert alert-<?= $t === 'success' ? 'success':'danger' ?>">
<?= session()->getFlashdata($t) ?>
</div>
<?php endif; endforeach; ?>

<div class="card shadow">
<div class="card-body">

<h4 class="text-center font-weight-bold mb-2">
🔐 Verifikasi OTP
</h4>

<p class="text-center text-muted mb-3">
Masukkan 6 digit kode dari WhatsApp
</p>

<form id="otpForm" method="post" action="<?= base_url('verify-otp') ?>">
<?= csrf_field() ?>

<input type="text"
       name="otp"
       id="otp"
       maxlength="6"
       class="form-control otp-input mb-3"
       autofocus
       required>

<button class="btn btn-success btn-block">
Verifikasi
</button>

</form>

</div>
</div>

</div>

<script>
const otp = document.getElementById('otp');
otp.addEventListener('input', function () {
  if (this.value.length === 6) {
    document.getElementById('otpForm').submit();
  }
});
</script>

</body>
</html>
