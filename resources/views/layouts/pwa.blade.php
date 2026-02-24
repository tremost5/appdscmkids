<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= esc($title ?? 'Presensi Guru') ?></title>

  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <meta name="theme-color" content="#22c55e">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="Presensi DSCM">

  <!-- PWA -->
  <link rel="manifest" href="<?= base_url('pwa/manifest.json') ?>">
  <link rel="apple-touch-icon" href="<?= base_url('pwa/icons/icon-192.png') ?>">

  <!-- CSS ringan -->
  <link rel="stylesheet" href="<?= base_url('assets/pwa/pwa.css') ?>">
</head>
<body>

@yield('content')

<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('<?= base_url('pwa/sw.js') ?>', {
    scope: '<?= rtrim(base_url('pwa'), '/') . '/' ?>'
  });
}
</script>

</body>
</html>
