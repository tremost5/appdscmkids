<?php
$uri = $uri ?? uri_string();
$quickLinks = [];
$showSearch = false;

if (str_contains($uri, 'admin/guru') || str_contains($uri, 'superadmin/guru')) {
    $showSearch = true;
    $quickLinks = [
        ['label' => 'Data Guru', 'url' => base_url(session('role_id') == 1 ? 'dashboard/superadmin/guru' : 'admin/guru')],
        ['label' => 'Tambah Guru', 'url' => base_url(session('role_id') == 1 ? 'dashboard/superadmin/guru/create' : 'admin/guru/create')],
    ];
}

if (str_contains($uri, 'guru/murid') || str_contains($uri, 'admin/murid') || str_contains($uri, 'superadmin/murid')) {
    $showSearch = true;
    $quickLinks = [
        ['label' => 'Data Murid', 'url' => base_url(str_contains($uri, 'guru/') ? 'guru/murid' : (str_contains($uri, 'superadmin/') ? 'dashboard/superadmin/murid' : 'admin/murid'))],
    ];
    if (str_contains($uri, 'guru/')) {
        $quickLinks[] = ['label' => 'Tambah Murid', 'url' => base_url('guru/murid/create')];
    }
}

if (str_contains($uri, 'rekap-absensi')) {
    $showSearch = true;
    $rekapBase = str_contains($uri, 'superadmin/') ? 'dashboard/superadmin/rekap-absensi' : 'admin/rekap-absensi';
    $kelasBase = str_contains($uri, 'superadmin/') ? 'dashboard/superadmin/rekap-absensi' : 'admin/rekap-absensi/kelas';
    $statBase = str_contains($uri, 'superadmin/') ? 'dashboard/superadmin/statistik-absensi' : 'admin/statistik';
    $quickLinks = [
        ['label' => 'Rekap', 'url' => base_url($rekapBase)],
        ['label' => 'Per Kelas', 'url' => base_url($kelasBase)],
        ['label' => 'Statistik', 'url' => base_url($statBase)],
    ];
}

if (str_contains($uri, 'guru/unity')) {
    $showSearch = true;
    $quickLinks = [
        ['label' => 'Presensi', 'url' => base_url('guru/absensi')],
        ['label' => 'Unity', 'url' => base_url('guru/unity')],
    ];
}
?>

<?php if ($showSearch || !empty($quickLinks)): ?>
<div class="quick-filter-bar" data-quick-filter>
  <?php if (!empty($quickLinks)): ?>
    <div class="quick-filter-bar__links">
      <?php foreach ($quickLinks as $lnk): ?>
        <a href="<?= $lnk['url'] ?>" class="quick-filter-chip"><?= esc($lnk['label']) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($showSearch): ?>
    <div class="quick-filter-bar__search">
      <i class="fas fa-search"></i>
      <input
        type="text"
        placeholder="Cari cepat di tabel/daftar..."
        data-quick-filter-input
      >
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>
