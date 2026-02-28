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
.install-modal .modal-content {
    border: 0;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
}
.install-modal .modal-header {
    border-bottom: 0;
    padding: 14px 18px 10px;
    background: linear-gradient(135deg, #0ea5e9, #2563eb);
    color: #fff;
}
.install-modal .modal-title {
    font-weight: 700;
}
.install-modal .close {
    color: #fff;
    opacity: .9;
}
.install-modal .modal-body {
    padding: 16px 18px 6px;
}
.install-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid #dbeafe;
    color: #1d4ed8;
    background: #eff6ff;
    font-size: .8rem;
    font-weight: 600;
    border-radius: 999px;
    padding: 5px 10px;
    margin-bottom: 10px;
}
.install-copy {
    color: #334155;
    margin-bottom: 10px;
}
.install-steps {
    margin: 0;
    padding-left: 20px;
    color: #334155;
}
.install-steps li {
    margin-bottom: 6px;
}
.install-note {
    font-size: .82rem;
    color: #64748b;
    margin-top: 8px;
}
.install-modal .modal-footer {
    border-top: 0;
    padding: 10px 18px 16px;
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

        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn btn-primary btn-block">
              <i class="fas fa-lock"></i> Login
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

<div class="modal fade install-modal" id="pwaInstallModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pasang Aplikasi Presensi</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="pwaInstallBody">
        <span class="install-badge" id="pwaInstallBadge"><i class="fas fa-mobile-alt"></i> PWA</span>
        <div class="install-copy" id="pwaInstallCopy">Tambahkan Presensi DSCM ke layar utama agar akses lebih cepat.</div>
        <ol class="install-steps" id="pwaInstallSteps"></ol>
        <div class="install-note" id="pwaInstallNote"></div>
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
  const input = document.getElementById('loginPassword');

  if (toggle && input) {
    const icon = toggle.querySelector('i');
    toggle.addEventListener('click', () => {
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      if (icon) {
        icon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
      }
    });
  }

  let deferredPrompt = null;
  const modalEl = document.getElementById('pwaInstallModal');
  const badgeEl = document.getElementById('pwaInstallBadge');
  const copyEl = document.getElementById('pwaInstallCopy');
  const stepsEl = document.getElementById('pwaInstallSteps');
  const noteEl = document.getElementById('pwaInstallNote');
  const installButton = document.getElementById('btnInstallPwa');
  const shownFlag = 'pwa-install-modal-shown-v3';

  const ua = navigator.userAgent.toLowerCase();
  const isIos = /iphone|ipad|ipod/.test(ua);
  const isSafari = /safari/.test(ua) && !/crios|fxios|edgios|chrome|android/.test(ua);
  const isAndroid = /android/.test(ua);
  const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

  function setInstallContent(platform, title, steps, note) {
    if (badgeEl) {
      badgeEl.innerHTML = '<i class="fas fa-mobile-alt"></i> ' + platform;
    }
    if (copyEl) {
      copyEl.textContent = title;
    }
    if (stepsEl) {
      stepsEl.innerHTML = '';
      steps.forEach((step) => {
        const li = document.createElement('li');
        li.innerHTML = step;
        stepsEl.appendChild(li);
      });
    }
    if (noteEl) {
      noteEl.textContent = note || '';
    }
  }

  function showModal() {
    if (!modalEl || !window.jQuery) return;
    window.jQuery(modalEl).modal('show');
  }

  function hideModal() {
    if (!modalEl || !window.jQuery) return;
    window.jQuery(modalEl).modal('hide');
  }

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw-guru.js', { scope: '/' }).catch(() => {});
  }

  if (isStandalone) return;

  if (isIos && isSafari) {
    if (!sessionStorage.getItem(shownFlag)) {
      sessionStorage.setItem(shownFlag, '1');
      setInstallContent(
        'iPhone / iPad',
        'Pasang aplikasi lewat Safari agar login lebih cepat.',
        [
          'Tap tombol <b>Share</b> di Safari.',
          'Pilih <b>Add to Home Screen</b>.',
          'Tap <b>Add</b> untuk selesai.'
        ],
        'Setelah ditambahkan, buka dari ikon di layar utama.'
      );
      installButton.textContent = 'Saya Mengerti';
      installButton.onclick = hideModal;
      showModal();
    }
    return;
  }

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;

    if (sessionStorage.getItem(shownFlag)) return;

    sessionStorage.setItem(shownFlag, '1');
    setInstallContent(
      'Android',
      'Install aplikasi Presensi untuk pengalaman lebih cepat.',
      [
        'Tap tombol <b>Install</b> di bawah ini.',
        'Konfirmasi pemasangan dari popup browser.',
        'Buka aplikasi dari home screen.'
      ],
      'Data tetap dari website yang sama, hanya aksesnya jadi seperti aplikasi.'
    );
    installButton.textContent = 'Install';
    installButton.onclick = async () => {
      if (!deferredPrompt) {
        hideModal();
        return;
      }
      deferredPrompt.prompt();
      await deferredPrompt.userChoice;
      deferredPrompt = null;
      hideModal();
    };
    showModal();
  });

  if (isAndroid && !sessionStorage.getItem(shownFlag)) {
    setTimeout(() => {
      if (deferredPrompt || sessionStorage.getItem(shownFlag)) return;
      sessionStorage.setItem(shownFlag, '1');
      setInstallContent(
        'Android',
        'Jika tombol install belum muncul, pasang manual dari menu browser.',
        [
          'Buka menu browser (<b>&#8942;</b>).',
          'Pilih <b>Install app</b> atau <b>Add to Home screen</b>.',
          'Konfirmasi lalu buka dari ikon baru.'
        ],
        ''
      );
      installButton.textContent = 'Tutup';
      installButton.onclick = hideModal;
      showModal();
    }, 1400);
  }

  window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    hideModal();
  });
});
</script>

<script src="/assets/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="/assets/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/assets/adminlte/js/adminlte.min.js"></script>

</body>
</html>
