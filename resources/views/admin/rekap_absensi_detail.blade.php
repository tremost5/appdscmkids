@extends('layouts/adminlte')
@section('content')

<?php
$modeAktif = $mode ?? 'all';
$isUnityMode = $modeAktif === 'unity';
$guruLabel = $modeAktif === 'all' ? 'PIC' : ($isUnityMode ? 'Mentor' : 'Guru');
$titleLabel = $modeAktif === 'all' ? 'Rekap Presensi' : 'Rekap Presensi '.ucfirst($modeAktif);
?>

<section class="content-header">
  <div class="container-fluid">
    <h4><?= esc($titleLabel) ?> <?= esc($tanggal) ?></h4>
  </div>
</section>

<section class="content">
<div class="container-fluid">

<!-- SUMMARY -->
<div class="alert alert-info d-flex flex-wrap gap-2">
  <span>👥 <?= (int)$summary['total_hadir'] ?> siswa</span>
  <span>🏫 <?= (int)$summary['total_kelas'] ?> kelas</span>

  <?php if (!empty($summary['total_dobel']) && $summary['total_dobel'] > 0): ?>
    <span class="badge badge-danger">
      🔴 <?= (int)$summary['total_dobel'] ?> presensi dobel
    </span>
  <?php endif; ?>
</div>

<!-- ACTION BUTTON -->
<div class="mb-3 d-flex flex-wrap gap-2">
  <a href="<?= base_url('admin/rekap-absensi/range') ?>"
     class="btn btn-secondary btn-sm">
     ⬅️ Kembali
  </a>

  <a href="<?= base_url('admin/rekap-absensi/export/pdf/'.$tanggal) ?>?unity=<?= esc($unity ?? '') ?>&mode=<?= esc($mode ?? 'all') ?>"
   class="btn btn-danger btn-sm">
    <i class="fas fa-file-pdf"></i>
     📄 PDF
  </a>

  <a href="<?= base_url('admin/rekap-absensi/export/excel/'.$tanggal) ?>?unity=<?= esc($unity ?? '') ?>&mode=<?= esc($mode ?? 'all') ?>"
   class="btn btn-success btn-sm">
    <i class="fas fa-file-excel"></i>
     📊 Excel
  </a>
</div>

<!-- MOBILE VIEW -->
<?php foreach ($rows as $r): ?>
<div class="card mb-2 <?= ($r['dobel'] > 1 ? 'border-danger' : '') ?>">
  <div class="card-body p-2">
    <strong>
      <?= esc($r['nama_depan'].' '.$r['nama_belakang']) ?>
      <?php if ($r['dobel'] > 1): ?>
        <span class="badge badge-danger ml-1">DOBEL</span>
      <?php endif; ?>
    </strong>

    <div class="small text-muted mt-1">
      📅 <?= esc($tanggal) ?> |
      Kelas <?= esc($r['kelas_id']) ?><br>
      🕒 <?= esc($r['jam']) ?> |
      📍 <?= esc($r['nama_lokasi'] ?? '-') ?><br>
      <?= $isUnityMode ? '🧑‍🤝‍🧑' : '👨‍🏫' ?> <?= esc(trim(($r['guru_depan'] ?? '').' '.($r['guru_belakang'] ?? ''))) ?>
      <?php if ($modeAktif === 'all'): ?>
        <br><span class="badge badge-info"><?= esc(ucfirst($r['jenis_presensi'] ?? 'reguler')) ?></span>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>

<!-- DESKTOP TABLE -->
<div class="table-responsive mt-4 d-none d-md-block">
<table class="table table-bordered table-sm">
<thead class="thead-light">
<tr>
  <th>Tanggal</th>
  <th>Nama</th>
  <th>Kelas</th>
  <?php if ($modeAktif === 'all'): ?>
    <th>Jenis</th>
  <?php endif; ?>
  <th>Jam</th>
  <th>Lokasi</th>
  <th><?= esc($guruLabel) ?></th>
  <th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr class="<?= ($r['dobel'] > 1 ? 'table-danger' : '') ?>">
  <td><?= esc($tanggal) ?></td>
  <td><?= esc($r['nama_depan'].' '.$r['nama_belakang']) ?></td>
  <td><?= esc($r['kelas_id']) ?></td>
  <?php if ($modeAktif === 'all'): ?>
    <td><?= esc(ucfirst($r['jenis_presensi'] ?? 'reguler')) ?></td>
  <?php endif; ?>
  <td><?= esc($r['jam']) ?></td>
  <td><?= esc($r['nama_lokasi'] ?? '-') ?></td>
  <td><?= esc(trim(($r['guru_depan'] ?? '').' '.($r['guru_belakang'] ?? ''))) ?></td>
  <td>
    <?php if ($r['dobel'] > 1): ?>
      <span class="badge badge-danger">DOBEL</span>
    <?php else: ?>
      <span class="badge badge-success">OK</span>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</div>
</section>

@endsection
