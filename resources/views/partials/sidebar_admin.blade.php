<nav class="mt-2 sidebar-premium">
<ul class="nav nav-pills nav-sidebar flex-column text-sm"
    data-widget="treeview"
    role="menu"
    data-accordion="false">
<?php
$canAdminGuru      = (int) setting('admin_guru', 1) === 1;
$canAdminAbsen     = (int) setting('admin_absen', 1) === 1;
$canAdminMateri    = (int) setting('admin_materi', 1) === 1;
$canAdminNaikKelas = (int) setting('admin_naik_kelas', 1) === 1;

$dobelUnresolvedCount = 0;
$guruNonaktifCount = 0;
$guruBaruDaftarCount = 0;
$guruAlertCount = 0;

if ($canAdminAbsen) {
  try {
    $db = \Config\Database::connect();
    $dobelCountRow = $db->query("
      SELECT COUNT(DISTINCT CONCAT(murid_id, '|', tanggal)) AS total
      FROM absensi_detail
      WHERE status = 'dobel'
    ")->getRowArray();
    $dobelUnresolvedCount = (int) ($dobelCountRow['total'] ?? 0);
  } catch (\Throwable $e) {
    $dobelUnresolvedCount = 0;
  }
}

if ($canAdminGuru) {
  try {
    $db = \Config\Database::connect();
    $fields = array_map('strtolower', $db->getFieldNames('users'));
    $hasCreatedAt = in_array('created_at', $fields, true);
    $todayStart = date('Y-m-d 00:00:00');

    $select = "SUM(CASE WHEN status = 'nonaktif' THEN 1 ELSE 0 END) AS guru_nonaktif";
    if ($hasCreatedAt) {
      $select .= ", SUM(CASE WHEN created_at >= " . $db->escape($todayStart) . " THEN 1 ELSE 0 END) AS guru_baru";
    }

    $row = $db->table('users')
      ->select($select, false)
      ->where('role_id', 3)
      ->get()
      ->getRowArray();

    $guruNonaktifCount = (int) ($row['guru_nonaktif'] ?? 0);
    $guruBaruDaftarCount = $hasCreatedAt ? (int) ($row['guru_baru'] ?? 0) : 0;
    $guruAlertCount = $guruNonaktifCount + $guruBaruDaftarCount;
  } catch (\Throwable $e) {
    $guruNonaktifCount = 0;
    $guruBaruDaftarCount = 0;
    $guruAlertCount = 0;
  }
}
?>

<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<style>
/* ===== ABSENSI DOBEL HIGHLIGHT ===== */
.absensi-warning {
  background: linear-gradient(90deg,#facc15,#fde047);
  color:#000 !important;
  font-weight:600;
  border-left:5px solid #dc2626;
}

.absensi-warning i,
.absensi-warning p {
  color:#000 !important;
}

.absensi-shake {
  animation: shake 1s infinite;
}

.guru-warning {
  background: linear-gradient(90deg,#fee2e2,#fecaca);
  color:#7f1d1d !important;
  font-weight:700;
  border-left:5px solid #dc2626;
}

.guru-warning i,
.guru-warning p {
  color:#7f1d1d !important;
}

@keyframes shake {
  0% { transform: translateX(0); }
  25% { transform: translateX(-2px); }
  50% { transform: translateX(2px); }
  75% { transform: translateX(-2px); }
  100% { transform: translateX(0); }
}
</style>

    <!-- DASHBOARD -->
    <li class="nav-item">
        <a href="<?= base_url('dashboard/admin') ?>"
           class="nav-link <?= uri_string() === 'dashboard/admin' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
        </a>
    </li>

    <li class="nav-header text-info">MANAJEMEN</li>

    <!-- GURU -->
    <?php if ($canAdminGuru): ?>
    <li class="nav-item">
        <a href="<?= base_url('admin/guru') ?>"
           id="menu-guru"
           class="nav-link <?= str_contains(uri_string(), 'admin/guru') ? 'active' : '' ?> <?= $guruAlertCount > 0 ? 'guru-warning' : '' ?>">
            <i class="nav-icon fas fa-users"></i>
            <p>
              Data Guru
                <span id="badge-guru-alert-icon"
                      class="badge badge-danger ml-2 <?= $guruAlertCount > 0 ? '' : 'd-none' ?>"
                      title="Notifikasi guru">
                  !
                </span>
                <span id="badge-guru-alert-total"
                      class="badge badge-warning ml-1 <?= $guruAlertCount > 0 ? '' : 'd-none' ?>"
                      title="Total item perlu dicek">
                  <?= $guruAlertCount ?>
                </span>
                  <span id="badge-guru-baru"
                        class="badge badge-info ml-1 <?= $guruBaruDaftarCount > 0 ? '' : 'd-none' ?>"
                        title="Pendaftar baru hari ini">
                    baru <?= $guruBaruDaftarCount ?>
                  </span>
            </p>
        </a>
    </li>
    <?php endif; ?>

    <!-- ================= ABSENSI ================= -->
    <?php
      $isAbsensi =
        str_contains(uri_string(), 'admin/rekap-absensi') ||
        str_contains(uri_string(), 'admin/absensi-dobel') ||
        str_contains(uri_string(), 'admin/statistik');
    ?>

    <?php if ($canAdminAbsen): ?>
    <li class="nav-item has-treeview <?= $isAbsensi ? 'menu-open' : '' ?>">
        <a href="#" class="nav-link <?= $isAbsensi ? 'active' : '' ?> <?= $dobelUnresolvedCount > 0 ? 'absensi-warning absensi-shake' : '' ?>" id="menuAbsensi">
            <i class="nav-icon fas fa-clipboard-check"></i>
            <p>
                Presensi
                <i class="right fas fa-angle-left"></i>
                <!-- BADGE GLOBAL -->
                <span id="badgeAbsensiGlobal"
                      class="badge badge-warning ml-2 <?= $dobelUnresolvedCount > 0 ? '' : 'd-none' ?>">!</span>
            </p>
        </a>

        <ul class="nav nav-treeview pl-2">

            <!-- ABSENSI DOBEL -->
            <li class="nav-item">
                <a href="<?= base_url('admin/absensi-dobel') ?>"
                   class="nav-link <?= $dobelUnresolvedCount > 0 ? 'absensi-warning absensi-shake animate__animated animate__pulse' : '' ?>"
                   id="menuDobel">
                    <i class="nav-icon fas fa-exclamation-triangle"></i>
                    <p>
                        Presensi Dobel
                        <span class="badge badge-danger ml-2 <?= $dobelUnresolvedCount > 0 ? '' : 'd-none' ?>"
                              id="badgeDobel"><?= $dobelUnresolvedCount ?></span>
                    </p>
                </a>
            </li>

            <!-- REKAP -->
            <li class="nav-item">
                <a href="<?= base_url('admin/rekap-absensi') ?>"
                   class="nav-link <?= str_contains(uri_string(), 'rekap-absensi') ? 'active' : '' ?>">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Rekap Presensi</p>
                </a>
            </li>

            <!-- STATISTIK -->
            <li class="nav-item">
                <a href="<?= base_url('admin/statistik') ?>"
                   class="nav-link <?= str_contains(uri_string(), 'admin/statistik') ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar nav-icon"></i>
                    <p>Statistik</p>
                </a>
            </li>

        </ul>
    </li>
    <?php endif; ?>

    <!-- FOTO KEGIATAN -->
    <li class="nav-item">
        <a href="<?= base_url('admin/foto-kegiatan') ?>"
           class="nav-link <?= str_contains(uri_string(), 'foto-kegiatan') ? 'active' : '' ?>">
            <i class="nav-icon fas fa-camera"></i>
            <p>Foto Kegiatan</p>
        </a>
    </li>

    <!-- AUDIT LOG -->
<?php if ($canAdminAbsen): ?>
<li class="nav-item">
    <a href="<?= base_url(
        'admin/audit-log?start=' . date('Y-m-d') . '&end=' . date('Y-m-d')
    ) ?>"
       class="nav-link <?= str_contains(uri_string(), 'audit-log') ? 'active' : '' ?>">
        <i class="nav-icon fas fa-history"></i>
        <p>Audit Log Guru</p>
    </a>
</li>
<?php endif; ?>


    <?php if ($canAdminAbsen): ?>
    <li class="nav-header text-success">EXPORT ABSENSI</li>

    <!-- EXPORT -->
    <li class="nav-item has-treeview <?= str_contains(uri_string(), 'export-excel') ? 'menu-open' : '' ?>">
        <a href="#" class="nav-link">
            <i class="nav-icon fas fa-file-export"></i>
            <p>
                Export
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>

        <ul class="nav nav-treeview pl-2">
            <li class="nav-item">
                <a href="<?= base_url('admin/export-excel/mingguan') ?>"
                   class="nav-link <?= str_contains(uri_string(), 'mingguan') ? 'active' : '' ?>">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Excel Mingguan</p>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?= base_url('admin/export-excel/bulanan') ?>"
                   class="nav-link <?= str_contains(uri_string(), 'bulanan') ? 'active' : '' ?>">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Excel Bulanan</p>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?= base_url('admin/export-excel/tahunan') ?>"
                   class="nav-link <?= str_contains(uri_string(), 'tahunan') ? 'active' : '' ?>">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Excel Tahunan</p>
                </a>
            </li>
        </ul>
    </li>
    <?php endif; ?>

    <!-- BAHAN AJAR -->
    <?php if ($canAdminMateri): ?>
    <li class="nav-item">
        <a href="<?= base_url('admin/bahan-ajar') ?>"
           class="nav-link <?= url_is('admin/bahan-ajar*') ? 'active' : '' ?>">
            <i class="nav-icon fas fa-book"></i>
            <p>Bahan Ajar</p>
        </a>
    </li>
    <?php endif; ?>

    <li class="nav-header text-warning">AKADEMIK</li>

    <!-- NAIK KELAS -->
    <?php if ($canAdminNaikKelas): ?>
    <li class="nav-item has-treeview <?= str_contains(uri_string(), 'naik-kelas') ? 'menu-open' : '' ?>">
        <a href="#" class="nav-link">
            <i class="nav-icon fas fa-level-up-alt"></i>
            <p>Kenaikan Kelas <i class="right fas fa-angle-left"></i></p>
        </a>

        <ul class="nav nav-treeview pl-2">
            <li class="nav-item">
                <a href="<?= base_url('admin/naik-kelas') ?>" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Proses</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('admin/naik-kelas/histori') ?>" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>History</p>
                </a>
            </li>
        </ul>
    </li>
    <?php endif; ?>

<li class="nav-item">
  <a href="<?= base_url('admin/ranking-murid') ?>"
     class="nav-link <?= str_contains(uri_string(),'ranking-murid')?'active':'' ?>">
    <i class="nav-icon fas fa-trophy text-warning"></i>
    <p>Ranking Murid Rajin</p>
  </a>
</li>
    <li class="nav-header text-danger">AKUN</li>

    <li class="nav-item">
        <a href="<?= base_url('logout') ?>" class="nav-link text-danger">
            <i class="nav-icon fas fa-sign-out-alt"></i>
            <p>Logout</p>
        </a>
    </li>

</ul>
</nav>

<script>
(function () {
  const canAdminAbsen = <?= $canAdminAbsen ? 'true' : 'false' ?>;

  function refreshAbsensiDobel() {
    if (!canAdminAbsen) return;

    const url = "<?= base_url('admin/absensi-dobel/count') ?>" + '?_t=' + Date.now();
    fetch(url, {
      cache: 'no-store',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(res => {
      const badge  = document.getElementById('badgeDobel');
      const global = document.getElementById('badgeAbsensiGlobal');
      const menu   = document.getElementById('menuDobel');

      if (!badge || !menu || !global) return;

      if (res.total > 0) {
        // badge merah
        badge.innerText = res.total;
        badge.classList.remove('d-none');

        // badge global !
        global.classList.remove('d-none');

        // MENU NYALA + GERAK
        const menuAbs = document.getElementById('menuAbsensi');
        if (menuAbs) {
          menuAbs.classList.add('absensi-warning');
          menuAbs.classList.add('absensi-shake');
        }
        menu.classList.add('absensi-warning');
        menu.classList.add('animate__animated','animate__pulse');
        menu.classList.add('absensi-shake');
      } else {
        badge.classList.add('d-none');
        global.classList.add('d-none');

        const menuAbs = document.getElementById('menuAbsensi');
        if (menuAbs) {
          menuAbs.classList.remove('absensi-warning');
          menuAbs.classList.remove('absensi-shake');
        }
        menu.classList.remove('absensi-warning');
        menu.classList.remove('animate__animated','animate__pulse');
        menu.classList.remove('absensi-shake');
      }
    })
    .catch(() => {
      // Pertahankan state terakhir bila gagal fetch.
    });
  }

  function refreshGuruNotif() {
    const menu = document.getElementById('menu-guru');
    const icon = document.getElementById('badge-guru-alert-icon');
    const total = document.getElementById('badge-guru-alert-total');
    const baru = document.getElementById('badge-guru-baru');

    if (!menu || !icon || !total || !baru) return;

    const url = "<?= base_url('admin/guru/notif-count') ?>" + '?_t=' + Date.now();
    fetch(url, {
      cache: 'no-store',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(res => {
      const totalAlert = Number(res.total_alert);
      const baruHariIni = Number(res.baru_hari_ini);
      if (!Number.isFinite(totalAlert) || !Number.isFinite(baruHariIni)) {
        return;
      }
      const hasAlert = totalAlert > 0;

      menu.classList.toggle('guru-warning', hasAlert);

      icon.classList.toggle('d-none', !hasAlert);
      total.classList.toggle('d-none', !hasAlert);
      total.innerText = String(totalAlert);

      baru.classList.toggle('d-none', baruHariIni <= 0);
      baru.innerText = 'baru ' + String(baruHariIni);
    })
    .catch(() => {
      // Jangan reset UI kalau fetch error/cache invalid.
    });
  }

  refreshAbsensiDobel();
  refreshGuruNotif();
  setInterval(refreshAbsensiDobel, 10000);
  setInterval(refreshGuruNotif, 30000);
})();
</script>

