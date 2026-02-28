<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Atur Ulang Password</title>
<link rel="icon" type="image/png" sizes="192x192" href="<?= base_url('pwa/icons/icon-192.png') ?>">
<link rel="apple-touch-icon" href="<?= base_url('pwa/icons/icon-192.png') ?>">

<link rel="stylesheet" href="<?= base_url('assets/adminlte/css/adminlte.min.css') ?>">

<style>
body {
  background: linear-gradient(135deg, #5b86e5, #36d1dc);
  min-height: 100vh;
}
.card {
  border-radius: 18px;
}
.big-input {
  font-size: 20px;
  padding: 14px;
}
</style>
</head>

<body class="d-flex align-items-center justify-content-center">

<div class="container">
<div class="row justify-content-center">
<div class="col-12 col-md-6 col-lg-5">

<div class="card shadow">
<div class="card-body">

<h4 class="text-center font-weight-bold mb-3">
  🔐 Atur Ulang Password
</h4>

<p class="text-center text-muted mb-4">
  Buat password baru Anda<br>
  <small>(Password ditampilkan untuk kemudahan)</small>
</p>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger">
  <?= session()->getFlashdata('error') ?>
</div>
<?php endif; ?>

<form method="post" action="<?= base_url('reset-password-wa') ?>">
<?= csrf_field() ?>

<div class="form-group">
  <label>Password Baru</label>
  <input type="text"
         name="password"
         class="form-control big-input"
         required>
</div>

<div class="form-group">
  <label>Ulangi Password</label>
  <input type="text"
         name="password_confirm"
         class="form-control big-input"
         required>
</div>

<button class="btn btn-primary btn-block btn-lg mt-3">
  ✅ Simpan & Masuk
</button>

</form>

</div>
</div>

</div>
</div>
</div>

</body>
</html>
