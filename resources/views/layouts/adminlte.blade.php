<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<?php
  $faviconIcoUrl = base_url('pwa/icons/icon-192.png');
  if (is_file(public_path('favicon/favicon.ico'))) {
      $faviconIcoUrl = base_url('favicon/favicon.ico');
  } elseif (is_file(public_path('favicon.ico')) && (int) @filesize(public_path('favicon.ico')) > 0) {
      $faviconIcoUrl = base_url('favicon.ico');
  }

  $favicon32Url = is_file(public_path('favicon/favicon-32.png'))
      ? base_url('favicon/favicon-32.png')
      : base_url('pwa/icons/icon-192.png');

  $favicon192Url = is_file(public_path('favicon/favicon-192.png'))
      ? base_url('favicon/favicon-192.png')
      : base_url('pwa/icons/icon-192.png');
?>
<!-- ===== FAVICON ===== -->
<link rel="icon" href="<?= $faviconIcoUrl ?>">
<link rel="shortcut icon" href="<?= $faviconIcoUrl ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?= $favicon32Url ?>">
<link rel="icon" type="image/png" sizes="192x192" href="<?= $favicon192Url ?>">

<title><?= esc($title ?? 'Dashboard') ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">

<link rel="stylesheet" href="<?= base_url('assets/adminlte/plugins/fontawesome-free/css/all.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/adminlte/css/adminlte.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/adminlte/plugins/toastr/toastr.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/custom/ui-phase1.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/custom/ui-phase2.css') ?>">

<!-- ===== PWA MANIFEST ===== -->
<link rel="manifest" href="<?= base_url('pwa/manifest.json') ?>">
<meta name="theme-color" content="#2563eb">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Dscmkids App">
<link rel="apple-touch-icon" href="<?= base_url('pwa/icons/icon-192.png') ?>">
</head>

<body class="hold-transition sidebar-mini layout-fixed ui-shell role-<?= (int) session('role_id') ?>" data-uri="<?= esc(uri_string()) ?>">
<div class="wrapper">

<?php
  // ===============================
// ROLE STANDARD (FINAL & WAJIB)
// ===============================
$role = session('role_id');

$fotoPath     = 'uploads/guru/';
$profilUrl    = 'dashboard';
$dashboardUrl = 'dashboard';

if ($role == 2) { // ADMIN
    $fotoPath     = 'uploads/admin/';
    $profilUrl    = 'admin/profil';          // ✅ FIX
    $dashboardUrl = 'dashboard/admin';
} elseif ($role == 3) { // GURU
    $fotoPath     = 'uploads/guru/';
    $profilUrl    = 'guru/profil';
    $dashboardUrl = 'dashboard/guru';
} elseif ($role == 1) { // SUPERADMIN
    $fotoPath     = 'uploads/admin/';
    $profilUrl    = 'dashboard/superadmin/profil';
    $dashboardUrl = 'superadmin/dashboard';
}


  $fotoUser = session('foto') ?: 'default.png';
  $namaUser = trim(session('nama_depan').' '.session('nama_belakang'));
  $uri = uri_string();
  $segments = array_values(array_filter(explode('/', $uri)));
  $labelMap = [
      'dashboard' => 'Dashboard',
      'superadmin' => 'Superadmin',
      'admin' => 'Admin',
      'guru' => 'Guru',
      'murid' => 'Murid',
      'profil' => 'Profil',
      'rekap-absensi' => 'Rekap Presensi',
      'statistik' => 'Statistik',
      'statistik-absensi' => 'Statistik Presensi',
      'bahan-ajar' => 'Bahan Ajar',
      'materi' => 'Materi',
      'kegiatan' => 'Kegiatan',
      'absensi' => 'Presensi',
      'unity' => 'Unity',
      'absensi-hari-ini' => 'Presensi Hari Ini',
      'absensi-dobel' => 'Presensi Dobel',
      'naik-kelas' => 'Naik Kelas',
      'audit-log' => 'Audit Log',
      'ranking-murid' => 'Ranking Murid',
      'tahun-ajaran' => 'Tahun Ajaran',
      'system-control' => 'System Control',
      'system-log' => 'System Log',
      'activity-log' => 'Activity Log',
      'users' => 'Role User',
      'monitoring' => 'Monitoring',
      'kelas' => 'Kelas',
      'create' => 'Tambah',
      'edit' => 'Edit',
      'detail' => 'Detail',
      'histori' => 'Histori',
  ];
  $breadcrumbs = ['Home'];
  foreach ($segments as $seg) {
      if (is_numeric($seg)) {
          continue;
      }
      $breadcrumbs[] = $labelMap[$seg] ?? ucwords(str_replace(['-', '_'], ' ', $seg));
  }
  $pageTitle = end($breadcrumbs) ?: 'Dashboard';
  $pageSubtitle = 'Ringkasan halaman dan aksi cepat.';
?>

<!-- NAVBAR -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <ul class="navbar-nav">
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#">
        <i class="fas fa-bars"></i>
      </a>
    </li>
  </ul>

  <ul class="navbar-nav ml-auto">
    <li class="nav-item dropdown">
      <a class="nav-link d-flex align-items-center" data-toggle="dropdown" href="#">
        <img src="<?= base_url($fotoPath.$fotoUser) ?>"
             class="img-circle elevation-2 mr-2"
             width="30" height="30" style="object-fit:cover">
        <span><?= esc(session('nama_depan')) ?></span>
      </a>

      <div class="dropdown-menu dropdown-menu-right">
        <a href="<?= base_url($profilUrl) ?>" class="dropdown-item">
          <i class="fas fa-user mr-2"></i> Profil Saya
        </a>
        <div class="dropdown-divider"></div>
        <a href="<?= base_url('logout') ?>" class="dropdown-item text-danger">
          <i class="fas fa-sign-out-alt mr-2"></i> Logout
        </a>
      </div>
    </li>
  </ul>
</nav>

<!-- SIDEBAR -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="<?= base_url($dashboardUrl) ?>" class="brand-link text-center">
    <span class="brand-text font-weight-bold">DSCMKIDS APP</span>
  </a>

  <div class="sidebar">

    <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
      <div class="image">
        <img src="<?= base_url($fotoPath.$fotoUser) ?>"
             class="img-circle elevation-2"
             width="45" height="45" style="object-fit:cover">
      </div>
      <div class="info">
        <a href="<?= base_url($profilUrl) ?>" class="d-block">
          <?= esc($namaUser) ?>
        </a>
        <small class="text-muted d-block" style="font-size:12px">
  Last login:
  <?= session('last_login')
    ? date('d M H:i', strtotime(session('last_login')))
    : '-' ?>
</small>
        <small class="text-success">● Online</small>
      </div>
    </div>

    <?php if ($role == 2): ?>
      <?= view('partials/sidebar_admin') ?>
    <?php elseif ($role == 3): ?>
      <?= view('partials/sidebar') ?>
    <?php elseif ($role == 1): ?>
      <?= view('partials/sidebar_superadmin') ?>
    <?php endif; ?>

  </div>
</aside>

<div class="content-wrapper app-reveal">
  <section class="content pt-3">
    <?= view('partials/page_header', ['pageTitle' => $pageTitle, 'pageSubtitle' => $pageSubtitle, 'breadcrumbs' => $breadcrumbs]) ?>
    <?= view('partials/quick_filter_bar', ['uri' => $uri]) ?>
    @yield('content')
  </section>
</div>
<?php if ($role == 1): ?>
<nav class="mobile-bottom-nav">
  <a href="<?= base_url('superadmin/dashboard') ?>" class="mobile-bottom-nav__link <?= in_array($uri, ['superadmin/dashboard', 'dashboard/superadmin', 'superadmin']) ? 'active' : '' ?>">
    <i class="fas fa-home"></i><span>Home</span>
  </a>
  <a href="<?= base_url('dashboard/superadmin/guru') ?>" class="mobile-bottom-nav__link <?= str_contains($uri, 'superadmin/guru') ? 'active' : '' ?>">
    <i class="fas fa-chalkboard-teacher"></i><span>Guru</span>
  </a>
  <a href="<?= base_url('dashboard/superadmin/rekap-absensi') ?>" class="mobile-bottom-nav__link <?= str_contains($uri, 'superadmin/rekap-absensi') ? 'active' : '' ?>">
    <i class="fas fa-clipboard-list"></i><span>Presensi</span>
  </a>
  <a href="<?= base_url('dashboard/superadmin/profil') ?>" class="mobile-bottom-nav__link <?= str_contains($uri, 'superadmin/profil') ? 'active' : '' ?>">
    <i class="fas fa-user-cog"></i><span>Profil</span>
  </a>
</nav>
<?php elseif ($role == 2): ?>
<nav class="mobile-bottom-nav">
  <a href="<?= base_url('dashboard/admin') ?>" class="mobile-bottom-nav__link <?= $uri === 'dashboard/admin' ? 'active' : '' ?>">
    <i class="fas fa-home"></i><span>Home</span>
  </a>
  <a href="<?= base_url('admin/guru') ?>" class="mobile-bottom-nav__link <?= str_contains($uri, 'admin/guru') ? 'active' : '' ?>">
    <i class="fas fa-users"></i><span>Guru</span>
  </a>
  <a href="<?= base_url('admin/rekap-absensi') ?>" class="mobile-bottom-nav__link <?= str_contains($uri, 'admin/rekap-absensi') ? 'active' : '' ?>">
    <i class="fas fa-clipboard-check"></i><span>Presensi</span>
  </a>
  <a href="<?= base_url('admin/profil') ?>" class="mobile-bottom-nav__link <?= str_contains($uri, 'admin/profil') ? 'active' : '' ?>">
    <i class="fas fa-user"></i><span>Profil</span>
  </a>
</nav>
<?php elseif ($role == 3): ?>
<nav class="mobile-bottom-nav">
  <a href="<?= base_url('dashboard/guru') ?>" class="mobile-bottom-nav__link <?= $uri === 'dashboard/guru' ? 'active' : '' ?>">
    <i class="fas fa-home"></i><span>Home</span>
  </a>
  <a href="<?= base_url('guru/absensi') ?>" class="mobile-bottom-nav__link <?= str_contains($uri, 'guru/absensi') ? 'active' : '' ?>">
    <i class="fas fa-clipboard-check"></i><span>Presensi</span>
  </a>
  <a href="<?= base_url('guru/unity') ?>" class="mobile-bottom-nav__link <?= str_contains($uri, 'guru/unity') ? 'active' : '' ?>">
    <i class="fas fa-star"></i><span>Unity</span>
  </a>
  <a href="<?= base_url('guru/murid') ?>" class="mobile-bottom-nav__link <?= str_contains($uri, 'guru/murid') ? 'active' : '' ?>">
    <i class="fas fa-user-graduate"></i><span>Murid</span>
  </a>
</nav>
<?php endif; ?>

</div>

<script src="<?= base_url('assets/adminlte/plugins/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/adminlte/js/adminlte.min.js') ?>"></script>
<script src="<?= base_url('assets/adminlte/plugins/toastr/toastr.min.js') ?>"></script>
<script src="<?= base_url('assets/custom/ui-phase2.js') ?>"></script>

<link rel="stylesheet" href="<?= base_url('assets/custom/sidebar-premium.css') ?>">
<script src="<?= base_url('assets/custom/sidebar-premium.js') ?>"></script>

<!-- ===== PWA SERVICE WORKER ===== -->
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    const swUrl = '<?= base_url('sw.js') ?>';
    const swScope = '<?= rtrim(base_url(), '/') . '/' ?>';
    navigator.serviceWorker.register(
      swUrl,
      { scope: swScope }
    );
  });
}
</script>

<script>
/* ===== GLOBAL MOBILE UX FIX ===== */
document.addEventListener('visibilitychange', ()=>{
  const fab = document.getElementById('fab');
  if(fab) fab.classList.remove('active');
});

window.addEventListener('beforeunload', ()=>{
  const fab = document.getElementById('fab');
  if(fab) fab.classList.remove('active');
});
</script>
<script>
(() => {
  const flashSuccess = <?= json_encode(session('success')) ?>;
  const flashError = <?= json_encode(session('error')) ?>;
  const flashWarning = <?= json_encode(session('warning')) ?>;

  if (typeof toastr !== 'undefined') {
    toastr.options = {
      closeButton: true,
      progressBar: true,
      newestOnTop: true,
      positionClass: 'toast-top-right',
      timeOut: 4200
    };

    if (flashSuccess) toastr.success(String(flashSuccess));
    if (flashError) toastr.error(String(flashError));
    if (flashWarning) toastr.warning(String(flashWarning));
  } else {
    if (flashSuccess) alert(String(flashSuccess));
    if (flashError) alert(String(flashError));
    if (flashWarning) alert(String(flashWarning));
  }
})();
</script>
<!-- di layouts/adminlte.php -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
