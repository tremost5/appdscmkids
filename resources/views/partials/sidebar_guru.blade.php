<?php
$role = (int) session()->get('role_id');
$isSuperadmin = $role === 1;

$canAbsensi = $isSuperadmin || (int) setting('guru_absen', 1) === 1;
$canMurid   = $isSuperadmin || (int) setting('guru_murid', 1) === 1;
$canMateri  = $isSuperadmin || (int) setting('guru_materi', 1) === 1;
$canKegiatan = $isSuperadmin || (int) setting('guru_kegiatan', 1) === 1;
?>

<nav class="mt-2 sidebar-premium">
<ul class="nav nav-pills nav-sidebar flex-column text-sm"
    data-widget="treeview"
    role="menu">

<!-- ================= DASHBOARD ================= -->
<li class="nav-item">
  <a href="<?= base_url('dashboard/guru') ?>"
     class="nav-link <?= uri_string()=='dashboard/guru'?'active':'' ?>">
    <i class="nav-icon fas fa-home"></i>
    <p>Dashboard</p>
  </a>
</li>

<!-- ================= ABSENSI ================= -->
<?php if ($canAbsensi): ?>
<li class="nav-item">
  <a href="<?= base_url('guru/absensi') ?>"
     class="nav-link <?= uri_string()=='guru/absensi'?'active':'' ?>">
    <i class="nav-icon fas fa-clipboard-check"></i>
    <p>Presensi</p>
  </a>
</li>
<?php endif; ?>

<!-- ================= ABSENSI HARI INI ================= -->
<?php if ($canAbsensi): ?>
<li class="nav-item">
  <a href="<?= base_url('guru/absensi-hari-ini') ?>"
     class="nav-link <?= uri_string()=='guru/absensi-hari-ini'?'active':'' ?>">
    <i class="nav-icon fas fa-edit"></i>
    <p>Presensi Hari Ini</p>
  </a>
</li>
<?php endif; ?>

<!-- ================= DATA MURID ================= -->
<?php if ($canMurid): ?>
<li class="nav-item">
  <a href="<?= base_url('guru/murid') ?>"
     class="nav-link <?= str_contains(uri_string(),'guru/murid')?'active':'' ?>">
    <i class="nav-icon fas fa-user-graduate"></i>
    <p>Data Murid</p>
  </a>
</li>
<?php endif; ?>

<!-- ================= MATERI ================= -->
<?php if ($canMateri): ?>
<li class="nav-item">
  <a href="<?= base_url('guru/materi') ?>"
     class="nav-link <?= uri_string()=='guru/materi'?'active':'' ?>">
    <i class="nav-icon fas fa-book"></i>
    <p>Materi Ajar</p>
  </a>
</li>
<?php endif; ?>

<!-- ================= KEGIATAN ================= -->
<?php if ($canKegiatan): ?>
<li class="nav-item">
  <a href="<?= base_url('guru/kegiatan') ?>"
     class="nav-link <?= str_contains(uri_string(),'guru/kegiatan')?'active':'' ?>">
    <i class="nav-icon fas fa-camera"></i>
    <p>Kegiatan</p>
  </a>
</li>
<?php endif; ?>

<!-- ================= PROFIL ================= -->
<li class="nav-item">
  <a href="<?= base_url('guru/profil') ?>"
     class="nav-link <?= uri_string()=='guru/profil'?'active':'' ?>">
    <i class="nav-icon fas fa-user-cog"></i>
    <p>Profil Saya</p>
  </a>
</li>

<!-- ================= LOGOUT ================= -->
<li class="nav-item mt-3">
  <a href="<?= base_url('logout') ?>" class="nav-link text-danger">
    <i class="nav-icon fas fa-sign-out-alt"></i>
    <p>Logout</p>
  </a>
</li>

</ul>
</nav>
