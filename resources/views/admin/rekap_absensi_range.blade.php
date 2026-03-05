@extends('layouts/adminlte')
@section('content')

<?php
$mapKelas = [
  1=>'PG',2=>'TKA',3=>'TKB',
  4=>'1',5=>'2',6=>'3',
  7=>'4',8=>'5',9=>'6',11=>'TR'
];
?>

<section class="content-header">
  <div class="container-fluid">
    <h1>Rekap Presensi</h1>
    <p class="text-muted">Ringkasan presensi per tanggal & kelas</p>
  </div>
</section>

<section class="content">
<div class="container-fluid">

<!-- ================= TAB ================= -->
<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link active"
       href="<?= base_url('admin/rekap-absensi') ?>">
      📅 Per Tanggal
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link"
       href="<?= base_url('admin/rekap-absensi/kelas') ?>?start=<?= esc($start ?? '') ?>&end=<?= esc($end ?? '') ?>">
      🏫 Per Kelas
    </a>
  </li>
</ul>

<!-- ================= FILTER ================= -->
<form method="get" class="row mb-3">
  <div class="col-4 col-md-3 mb-2">
    <label class="small">Dari</label>
    <input type="date" name="start"
           value="<?= esc($start ?? '') ?>"
           class="form-control form-control-sm" required>
  </div>

  <div class="col-4 col-md-3 mb-2">
    <label class="small">Sampai</label>
    <input type="date" name="end"
           value="<?= esc($end ?? '') ?>"
           class="form-control form-control-sm" required>
  </div>

  <div class="col-4 col-md-2 mb-2">
    <label class="small">Kelas</label>
    <select name="kelas" class="form-control form-control-sm">
      <option value="">Semua</option>
      <?php foreach ($mapKelas as $id=>$nama): ?>
        <option value="<?= $id ?>" <?= (($kelas ?? '')==$id)?'selected':'' ?>>
          <?= esc($nama) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-4 col-md-2 mb-2">
    <label class="small">Unity</label>
    <select name="unity" class="form-control form-control-sm">
      <option value="">Semua</option>
      <?php foreach (array_keys(unityMetaMap()) as $u): ?>
        <option value="<?= esc($u) ?>" <?= (($unity ?? '')===$u)?'selected':'' ?>>
          <?= esc($u) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-2 mb-2 d-flex align-items-end">
    <button class="btn btn-primary btn-sm btn-block">
      🔍 Cari
    </button>
  </div>
</form>

<?php if (empty($start) || empty($end)): ?>
  <div class="alert alert-warning">
    ⚠️ Pilih rentang tanggal terlebih dahulu
  </div>
<?php endif; ?>

<hr>

<!-- ================= HASIL ================= -->
<?php if (!empty($rows)): ?>
  <?php foreach ($rows as $r): ?>
    <div class="card mb-2">
      <div class="card-body">
        <strong><?= esc($r['tanggal']) ?></strong>

        <div class="small text-muted mt-1">
          👥 <?= $r['total_hadir'] ?> siswa |
          🏫 <?= $r['total_kelas'] ?> kelas |
          👨‍🏫 <?= $r['total_guru'] ?> guru
        </div>

        <a href="<?= base_url('admin/rekap-absensi/detail/'.$r['tanggal']) ?>?kelas=<?= esc($kelas ?? '') ?>&unity=<?= esc($unity ?? '') ?>"
           class="btn btn-sm btn-outline-primary mt-2">
          📂 Lihat Detail
        </a>
      </div>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="text-center text-muted py-4">
    Tidak ada data presensi
  </div>
<?php endif; ?>

</div>
</section>

@endsection
