<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?? 'Dashboard' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">

    <link rel="stylesheet" href="/assets/adminlte/css/adminlte.min.css">
    <link rel="stylesheet" href="/assets/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/assets/custom/ui-phase1.css">
    <link rel="stylesheet" href="/assets/custom/ui-phase2.css">
</head>

<body class="hold-transition sidebar-mini layout-fixed ui-shell" data-uri="<?= esc(uri_string()) ?>">
<div class="wrapper">
    <?php
      $uri = uri_string();
      $segments = array_values(array_filter(explode('/', $uri)));
      $breadcrumbs = ['Home'];
      foreach ($segments as $seg) {
          if (is_numeric($seg)) continue;
          $breadcrumbs[] = ucwords(str_replace(['-', '_'], ' ', $seg));
      }
      $pageTitle = end($breadcrumbs) ?: 'Dashboard';
    ?>

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a href="/logout" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="/dashboard" class="brand-link">
            <span class="brand-text font-weight-light">PRESENSI DSCM</span>
        </a>

        <div class="sidebar">
            <?= view('partials/sidebar') ?>
        </div>
    </aside>

    <!-- Content -->
    <div class="content-wrapper p-3 app-reveal">
        <?= view('partials/page_header', ['pageTitle' => $pageTitle, 'pageSubtitle' => 'Ringkasan halaman dan aksi cepat.', 'breadcrumbs' => $breadcrumbs]) ?>
        <?= view('partials/quick_filter_bar', ['uri' => $uri]) ?>
        @yield('content')
    </div>

</div>

<script src="/assets/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="/assets/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/assets/adminlte/js/adminlte.min.js"></script>
<script src="/assets/custom/ui-phase2.js"></script>
</body>
</html>
