<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Login Sistem Presensi</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="manifest" href="/pwa/manifest.json">
<meta name="theme-color" content="#2563eb">

<link rel="stylesheet" href="/assets/adminlte/plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="/assets/adminlte/css/adminlte.min.css">

<style>
body {
    background: linear-gradient(135deg, #f857a6, #5b86e5);
}
.login-box {
    margin-top: 10vh;
}
.login-logo b {
    color: #fff;
}
.card {
    border-radius: 12px;
}
</style>
</head>

<body class="hold-transition login-page">

<div class="login-box">
  <div class="login-logo">
    <b>DSCM</b> Presensi
  </div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success">
    <?= session()->getFlashdata('success') ?>
  </div>
<?php endif; ?>

  <div class="card">
    <div class="card-body login-card-body">

      <p class="login-box-msg">Silakan login</p>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
          <?= session()->getFlashdata('error') ?>
        </div>
      <?php endif; ?>

      <form action="/login" method="post">
        <?= csrf_field() ?>

        <!-- USERNAME -->
        <div class="input-group mb-3">
          <input type="text"
                 name="username"
                 class="form-control"
                 placeholder="Username"
                 required
                 autofocus>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
        </div>

        <!-- PASSWORD -->
        <div class="form-group">
  <label>Password</label>

  <div class="input-group">
  <input type="password"
         name="password"
         id="loginPassword"
         class="form-control"
         placeholder="Password"
         required>

  <div class="input-group-append">
    <span class="input-group-text" id="toggleLoginPassword" style="cursor:pointer">
      <i class="fas fa-eye"></i>
    </span>
  </div>
</div>
</div>



        <!-- CAPTCHA -->
        <div class="input-group mb-3">
          <input type="number"
                 name="captcha"
                 class="form-control"
                 placeholder="<?= esc(session()->get('captcha_q')) ?>"
                 required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-shield-alt"></span>
            </div>
          </div>
        </div>

        <!-- SUBMIT -->
        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn btn-primary btn-block">
              🔐 Login
            </button>
          </div>
        </div>
      </form>

      <hr>

      <p class="mb-1 text-center">
        <a href="/forgot">Lupa Password?</a>
      </p>

      <p class="mb-0 text-center">
        <a href="/register-guru" class="text-center">
          Daftar sebagai Guru
        </a>
      </p>

    </div>
  </div>
</div>
<div class="modal fade" id="pwaInstallModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Install Aplikasi</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        Tambahkan Presensi DSCM ke layar utama agar akses lebih cepat.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Nanti</button>
        <button type="button" class="btn btn-primary" id="btnInstallPwa">Install</button>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('toggleLoginPassword');
  const input  = document.getElementById('loginPassword');
  const icon   = toggle.querySelector('i');

  toggle.addEventListener('click', () => {
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    icon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
  });
});
</script>

<script>
(() => {
  let deferredPrompt = null;
  const installButton = document.getElementById('btnInstallPwa');
  const shownFlag = 'pwa-install-modal-shown';

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/pwa/sw.js', { scope: '/' }).catch(() => {});
  }

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;

    if (!sessionStorage.getItem(shownFlag)) {
      sessionStorage.setItem(shownFlag, '1');
      $('#pwaInstallModal').modal('show');
    }
  });

  installButton?.addEventListener('click', async () => {
    if (!deferredPrompt) {
      $('#pwaInstallModal').modal('hide');
      return;
    }

    deferredPrompt.prompt();
    await deferredPrompt.userChoice;
    deferredPrompt = null;
    $('#pwaInstallModal').modal('hide');
  });

  window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    $('#pwaInstallModal').modal('hide');
  });
})();
</script>


<script src="/assets/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="/assets/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/assets/adminlte/js/adminlte.min.js"></script>

</body>
</html>
