<?php
$role = (int) session()->get('role_id');
$isSuperadmin = $role === 1;

$canAbsensi = $isSuperadmin || (int) setting('guru_absen', 1) === 1;
$canMurid   = $isSuperadmin || (int) setting('guru_murid', 1) === 1;
$canMateri  = $isSuperadmin || (int) setting('guru_materi', 1) === 1;
$canKegiatan = $isSuperadmin || (int) setting('guru_kegiatan', 1) === 1;

$uri = uri_string();
$presensiGroupOpen = in_array($uri, ['guru/absensi', 'guru/absensi-hari-ini'], true);
$unityGroupOpen = in_array($uri, ['guru/unity', 'guru/unity-hari-ini'], true);
?>

<nav class="mt-2 sidebar-premium">
<ul class="nav nav-pills nav-sidebar flex-column text-sm"
    data-widget="treeview"
    role="menu">

<li class="nav-item">
  <a href="<?= base_url('dashboard/guru') ?>"
     class="nav-link <?= $uri=='dashboard/guru'?'active':'' ?>">
    <i class="nav-icon fas fa-home"></i>
    <p>Dashboard</p>
  </a>
</li>

<?php if ($canAbsensi): ?>
<li class="nav-item has-treeview <?= $presensiGroupOpen ? 'menu-open' : '' ?>">
  <a href="#" class="nav-link <?= $presensiGroupOpen ? 'active' : '' ?>">
    <i class="nav-icon fas fa-clipboard-check"></i>
    <p>
      Presensi
      <i class="right fas fa-angle-left"></i>
    </p>
  </a>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="<?= base_url('guru/absensi') ?>" class="nav-link <?= $uri=='guru/absensi'?'active':'' ?>">
        <i class="far fa-circle nav-icon"></i>
        <p>Presensi</p>
      </a>
    </li>
    <li class="nav-item">
      <a href="<?= base_url('guru/absensi-hari-ini') ?>" class="nav-link <?= $uri=='guru/absensi-hari-ini'?'active':'' ?>">
        <i class="far fa-circle nav-icon"></i>
        <p>Presensi Hari Ini</p>
      </a>
    </li>
  </ul>
</li>
<?php endif; ?>

<?php if ($canAbsensi): ?>
<li class="nav-item has-treeview <?= $unityGroupOpen ? 'menu-open' : '' ?>">
  <a href="#" class="nav-link <?= $unityGroupOpen ? 'active' : '' ?>">
    <i class="nav-icon fas fa-star"></i>
    <p>
      Unity
      <i class="right fas fa-angle-left"></i>
    </p>
  </a>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="<?= base_url('guru/unity') ?>" class="nav-link <?= $uri=='guru/unity'?'active':'' ?>">
        <i class="far fa-circle nav-icon"></i>
        <p>Presensi Unity</p>
      </a>
    </li>
    <li class="nav-item">
      <a href="<?= base_url('guru/unity-hari-ini') ?>" class="nav-link <?= $uri=='guru/unity-hari-ini'?'active':'' ?>">
        <i class="far fa-circle nav-icon"></i>
        <p>Unity Hari Ini</p>
      </a>
    </li>
  </ul>
</li>
<?php endif; ?>

<?php if ($canMurid): ?>
<li class="nav-item">
  <a href="<?= base_url('guru/murid') ?>"
     class="nav-link <?= str_contains($uri,'guru/murid')?'active':'' ?>">
    <i class="nav-icon fas fa-user-graduate"></i>
    <p>Data Murid</p>
  </a>
</li>
<?php endif; ?>

<?php if ($canMateri): ?>
<li class="nav-item">
  <a href="<?= base_url('guru/materi') ?>"
     class="nav-link <?= $uri=='guru/materi'?'active':'' ?>">
    <i class="nav-icon fas fa-book"></i>
    <p>Materi Ajar</p>
  </a>
</li>
<?php endif; ?>

<?php if ($canKegiatan): ?>
<li class="nav-item">
  <a href="<?= base_url('guru/kegiatan') ?>"
     class="nav-link <?= str_contains($uri,'guru/kegiatan')?'active':'' ?>">
    <i class="nav-icon fas fa-camera"></i>
    <p>Kegiatan</p>
  </a>
</li>
<?php endif; ?>

<li class="nav-item">
  <a href="<?= base_url('guru/profil') ?>"
     class="nav-link <?= $uri=='guru/profil'?'active':'' ?>">
    <i class="nav-icon fas fa-user-cog"></i>
    <p>Profil Saya</p>
  </a>
</li>

<li class="nav-item mt-3">
  <a href="<?= base_url('logout') ?>" class="nav-link text-danger">
    <i class="nav-icon fas fa-sign-out-alt"></i>
    <p>Logout</p>
  </a>
</li>

</ul>
</nav>
