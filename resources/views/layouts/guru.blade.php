<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title><?= $title ?? 'Guru' ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">

<!-- PWA -->
<link rel="manifest" href="<?= base_url('pwa/manifest.json') ?>">
<meta name="theme-color" content="#2563eb">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Dscmkids App">
<link rel="apple-touch-icon" href="<?= base_url('pwa/icons/icon-192.png') ?>">

<link rel="stylesheet" href="<?= base_url('assets/adminlte/css/adminlte.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/adminlte/plugins/fontawesome-free/css/all.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/custom/ui-phase1.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/custom/ui-phase2.css') ?>">
</head>

<body class="hold-transition sidebar-mini layout-fixed ui-shell role-3" data-uri="<?= esc(uri_string()) ?>">

<div class="wrapper">
<?php
  $uri = uri_string();
  $segments = array_values(array_filter(explode('/', $uri)));
  $labelMap = [
      'dashboard' => 'Dashboard',
      'guru' => 'Guru',
      'absensi' => 'Presensi',
      'unity' => 'Unity',
      'absensi-hari-ini' => 'Presensi Hari Ini',
      'murid' => 'Murid',
      'materi' => 'Materi',
      'kegiatan' => 'Kegiatan',
      'profil' => 'Profil',
      'create' => 'Tambah',
      'edit' => 'Edit',
      'detail' => 'Detail',
  ];
  $breadcrumbs = ['Home'];
  foreach ($segments as $seg) {
      if (is_numeric($seg)) {
          continue;
      }
      $breadcrumbs[] = $labelMap[$seg] ?? ucwords(str_replace(['-', '_'], ' ', $seg));
  }
  $pageTitle = end($breadcrumbs) ?: 'Dashboard';
?>

<?= view('partials/sidebar_guru') ?>

<div class="content-wrapper p-2 app-reveal">
<?= view('partials/page_header', ['pageTitle' => $pageTitle, 'pageSubtitle' => 'Ringkasan halaman dan aksi cepat.', 'breadcrumbs' => $breadcrumbs]) ?>
<?= view('partials/quick_filter_bar', ['uri' => $uri]) ?>
@yield('content')
</div>

</div>

<script src="<?= base_url('assets/adminlte/plugins/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/adminlte/js/adminlte.min.js') ?>"></script>
<script src="<?= base_url('assets/custom/ui-phase2.js') ?>"></script>

<!-- REGISTER SERVICE WORKER -->
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    const swUrl = '<?= base_url('pwa/sw.js') ?>';
    const swScope = '<?= rtrim(base_url('pwa'), '/') . '/' ?>';
    navigator.serviceWorker.register(swUrl, { scope: swScope });
  });
}
</script>

</body>
</html>
