<nav class="mt-2 sidebar-premium">
<ul class="nav nav-pills nav-sidebar flex-column text-sm" data-widget="treeview" role="menu">

<!-- =======================
DASHBOARD
======================= -->
<li class="nav-item">
  <a href="<?= base_url('superadmin/dashboard') ?>"
     class="nav-link <?= in_array(uri_string(), ['dashboard/superadmin', 'superadmin', 'superadmin/dashboard'])?'active':'' ?>">
    <i class="nav-icon fas fa-home"></i>
    <p>Dashboard</p>
  </a>
</li>

<!-- =======================
MASTER DATA
======================= -->
<li class="nav-header">MASTER DATA</li>

<li class="nav-item">
  <a href="<?= base_url('dashboard/superadmin/guru') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/guru')?'active':'' ?>">
    <i class="nav-icon fas fa-chalkboard-teacher"></i>
    <p>Data Guru</p>
  </a>
</li>

<li class="nav-item">
  <a href="<?= base_url('dashboard/superadmin/murid') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/murid')?'active':'' ?>">
    <i class="nav-icon fas fa-user-graduate"></i>
    <p>Data Murid</p>
  </a>
</li>

<li class="nav-item">
  <a href="<?= base_url('dashboard/superadmin/kelas') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/kelas')?'active':'' ?>">
    <i class="nav-icon fas fa-layer-group"></i>
    <p>Data Kelas</p>
  </a>
</li>

<!-- =======================
AKADEMIK
======================= -->
<li class="nav-header">AKADEMIK</li>

<li class="nav-item">
  <a href="<?= base_url('dashboard/superadmin/materi') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/materi')?'active':'' ?>">
    <i class="nav-icon fas fa-book"></i>
    <p>Materi Ajar</p>
  </a>
</li>

<li class="nav-item">
  <a href="<?= base_url('dashboard/superadmin/naik-kelas') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/naik-kelas')?'active':'' ?>">
    <i class="nav-icon fas fa-level-up-alt"></i>
    <p>Naik Kelas</p>
  </a>
</li>

<!-- =======================
ABSENSI
======================= -->
<li class="nav-header">ABSENSI</li>

<li class="nav-item">
  <a href="<?= base_url('dashboard/superadmin/rekap-absensi') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/rekap-absensi')?'active':'' ?>">
    <i class="nav-icon fas fa-clipboard-list"></i>
    <p>Rekap Presensi</p>
  </a>
</li>

<li class="nav-item">
  <a href="<?= base_url('dashboard/superadmin/statistik-absensi') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/statistik-absensi')?'active':'' ?>">
    <i class="nav-icon fas fa-chart-bar"></i>
    <p>Statistik Presensi</p>
  </a>
</li>

<li class="nav-item">
  <a href="<?= base_url('dashboard/superadmin/absensi-dobel') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/absensi-dobel')?'active':'' ?>">
    <i class="nav-icon fas fa-exclamation-triangle"></i>
    <p>Presensi Dobel</p>
  </a>
</li>

<!-- =======================
EXPORT
======================= -->
<li class="nav-header">EXPORT</li>

<li class="nav-item">
  <a href="<?= base_url('dashboard/superadmin/export-excel/mingguan') ?>" class="nav-link">
    <i class="nav-icon fas fa-file-excel"></i>
    <p>Export Mingguan</p>
  </a>
</li>

<li class="nav-item">
  <a href="<?= base_url('dashboard/superadmin/export-excel/bulanan') ?>" class="nav-link">
    <i class="nav-icon fas fa-file-excel"></i>
    <p>Export Bulanan</p>
  </a>
</li>

<li class="nav-item">
  <a href="<?= base_url('dashboard/superadmin/export-excel/tahunan') ?>" class="nav-link">
    <i class="nav-icon fas fa-file-excel"></i>
    <p>Export Tahunan</p>
  </a>
</li>

<!-- =======================
SYSTEM
======================= -->
<li class="nav-header">SYSTEM</li>
<li class="nav-item">
  <a href="<?= base_url('superadmin/monitoring') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/monitoring')?'active':'' ?>">
    <i class="nav-icon fas fa-satellite-dish"></i>
    <p>Monitoring</p>
  </a>
</li>

<li class="nav-item">
  <a href="<?= base_url('superadmin/users') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/users')?'active':'' ?>">
    <i class="nav-icon fas fa-user-shield"></i>
    <p>Role User</p>
  </a>
</li>

<li class="nav-item">
  <a href="<?= base_url('superadmin/wa-template') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/wa-template')?'active':'' ?>">
    <i class="nav-icon fab fa-whatsapp"></i>
    <p>WA Template</p>
  </a>
</li>

<li class="nav-item">
  <a href="<?= base_url('superadmin/tahun-ajaran') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/tahun-ajaran')?'active':'' ?>">
    <i class="nav-icon fas fa-calendar-alt"></i>
    <p>Tahun Ajaran</p>
  </a>
</li>

<li class="nav-item">
  <a href="<?= base_url('superadmin/system-control') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/system-control')?'active':'' ?>">
    <i class="nav-icon fas fa-sliders-h"></i>
    <p>System Control</p>
  </a>
</li>

<li class="nav-item">
  <a href="<?= base_url('superadmin/system-log') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/system-log')?'active':'' ?>">
    <i class="nav-icon fas fa-file-alt"></i>
    <p>System Log</p>
  </a>
</li>

<li class="nav-item">
  <a href="<?= base_url('superadmin/activity-log') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/activity-log')?'active':'' ?>">
    <i class="nav-icon fas fa-history"></i>
    <p>Log Admin/Guru</p>
  </a>
</li>

<li class="nav-item">
  <a href="<?= base_url('dashboard/superadmin/audit-log') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/audit-log')?'active':'' ?>">
    <i class="nav-icon fas fa-shield-alt"></i>
    <p>Audit Log</p>
  </a>
</li>

<li class="nav-item">
  <a href="<?= base_url('dashboard/superadmin/profil') ?>"
     class="nav-link <?= str_contains(uri_string(),'superadmin/profil')?'active':'' ?>">
    <i class="nav-icon fas fa-user-cog"></i>
    <p>Profil Saya</p>
  </a>
</li>

<!-- =======================
LOGOUT
======================= -->
<li class="nav-item mt-3">
  <a href="<?= base_url('logout') ?>" class="nav-link text-danger">
    <i class="nav-icon fas fa-sign-out-alt"></i>
    <p>Logout</p>
  </a>
</li>

</ul>
</nav>
