@extends('layouts/adminlte')
@section('content')

<section class="content-header">
  <div class="container-fluid">
    <h4>Detail Presensi <?= esc($tanggal) ?></h4>
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

  <a href="<?= base_url('admin/rekap-absensi/export/pdf/'.$tanggal) ?>?unity=<?= esc($unity ?? '') ?>"
   class="btn btn-danger btn-sm">
    <i class="fas fa-file-pdf"></i>
     📄 PDF
  </a>

  <a href="<?= base_url('admin/rekap-absensi/export/excel/'.$tanggal) ?>?unity=<?= esc($unity ?? '') ?>"
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
      <?= unityBadge($r['unity'] ?? '') ?> <?= esc($r['unity'] ?? '-') ?><br>
      🕒 <?= esc($r['jam']) ?> |
      📍 <?= esc($r['lokasi_id']) ?><br>
      👨‍🏫 <?= esc(trim(($r['guru_depan'] ?? '').' '.($r['guru_belakang'] ?? ''))) ?>
    </div>
  </div>
</div>
<?php endforeach; ?>

<!-- DESKTOP TABLE -->
<div class="table-responsive mt-4 d-none d-md-block">
<table class="table table-bordered table-sm">
<thead class="thead-light">
<tr>
  <th>Nama</th>
  <th>Kelas</th>
  <th>Unity</th>
  <th>Jam</th>
  <th>Lokasi</th>
  <th>Guru</th>
  <th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr class="<?= ($r['dobel'] > 1 ? 'table-danger' : '') ?>">
  <td><?= esc($r['nama_depan'].' '.$r['nama_belakang']) ?></td>
  <td><?= esc($r['kelas_id']) ?></td>
  <td><?= unityBadge($r['unity'] ?? '') ?> <?= esc($r['unity'] ?? '-') ?></td>
  <td><?= esc($r['jam']) ?></td>
  <td><?= esc($r['lokasi_id']) ?></td>
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
