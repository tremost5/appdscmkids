<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lupa Password</title>
<link rel="icon" type="image/png" sizes="192x192" href="<?= base_url('pwa/icons/icon-192.png') ?>">
<link rel="apple-touch-icon" href="<?= base_url('pwa/icons/icon-192.png') ?>">

<link rel="stylesheet" href="<?= base_url('assets/adminlte/css/adminlte.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/adminlte/plugins/fontawesome-free/css/all.min.css') ?>">

<style>
body {
  background: linear-gradient(135deg, #5b86e5, #36d1dc);
  min-height: 100vh;
}

.card {
  border-radius: 16px;
}

.option-card {
  border: 2px solid #eee;
  border-radius: 14px;
  padding: 20px;
  text-align: center;
  transition: .2s;
}

.option-card:hover {
  border-color: #007bff;
  box-shadow: 0 10px 25px rgba(0,0,0,.08);
}

.option-icon {
  font-size: 42px;
  margin-bottom: 10px;
}

/* ===== TOAST NOTIF ===== */
.toast-notif {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 9999;
  padding: 14px 20px;
  border-radius: 8px;
  color: #fff;
  font-weight: 500;
  box-shadow: 0 8px 20px rgba(0,0,0,.2);
  animation: slideDown .4s ease;
}

.toast-notif.success { background: #28a745; }
.toast-notif.error   { background: #dc3545; }

@keyframes slideDown {
  from { opacity:0; transform:translate(-50%, -20px); }
  to   { opacity:1; transform:translate(-50%, 0); }
}
</style>
</head>

<body class="d-flex align-items-center justify-content-center">

<?php if (session()->getFlashdata('success')): ?>
<div class="toast-notif success">
  <?= session()->getFlashdata('success') ?>
</div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
<div class="toast-notif error">
  <?= session()->getFlashdata('error') ?>
</div>
<?php endif; ?>

<div class="container">
<div class="row justify-content-center">
<div class="col-12 col-md-6 col-lg-5">

<div class="card shadow">
<div class="card-body">

<h4 class="text-center font-weight-bold mb-2">
  🔐 Lupa Password
</h4>

<p class="text-center text-muted mb-4">
  Pilih cara untuk mengatur ulang password Anda
</p>

<!-- ===== VIA WHATSAPP ===== -->
<div class="option-card mb-3">
  <div class="option-icon text-success">
    <i class="fab fa-whatsapp"></i>
  </div>
  <h5 class="font-weight-bold">Via WhatsApp</h5>
  <p class="text-muted small">
    Gunakan nomor WhatsApp yang terdaftar<br>
    untuk menerima kode OTP
  </p>

  <form method="post" action="<?= base_url('forgot/wa') ?>">
    <?= csrf_field() ?>
    <input type="text"
           name="no_hp"
           class="form-control mb-2"
           placeholder="Contoh: 08xxxxxxxxxx"
           required>
    <button class="btn btn-success btn-block">
      Kirim OTP WhatsApp
    </button>
  </form>
</div>

<!-- ===== VIA EMAIL ===== -->
<div class="option-card">
  <div class="option-icon text-primary">
    <i class="fas fa-envelope"></i>
  </div>
  <h5 class="font-weight-bold">Via Email</h5>
  <p class="text-muted small">
    Link reset password akan dikirim<br>
    ke email terdaftar
  </p>

  <form method="post" action="<?= base_url('forgot/email') ?>">
    <?= csrf_field() ?>
    <input type="email"
           name="email"
           class="form-control mb-2"
           placeholder="Email terdaftar"
           required>
    <button class="btn btn-primary btn-block">
      Kirim Link Email
    </button>
  </form>
</div>

<hr>

<a href="<?= base_url('login') ?>" class="btn btn-link btn-block">
  ⬅ Kembali ke Login
</a>

</div>
</div>

</div>
</div>
</div>

<script>
setTimeout(() => {
  const toast = document.querySelector('.toast-notif');
  if (toast) toast.remove();
}, 4000);
</script>

</body>
</html>
