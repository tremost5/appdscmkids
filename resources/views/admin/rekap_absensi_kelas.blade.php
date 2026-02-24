@extends('layouts/adminlte')
@section('content')

<?php
$mapKelas = [
  1=>'PG',2=>'TKA',3=>'TKB',
  4=>'1',5=>'2',6=>'3',
  7=>'4',8=>'5',9=>'6'
];

$kelasAktif = $kelas ?? '';
?>

<section class="content-header">
  <div class="container-fluid">
    <h1>Rekap Presensi Per Kelas</h1>
    <p class="text-muted">
      Periode
      <?= esc($start ?? '-') ?> s/d <?= esc($end ?? '-') ?>
    </p>
  </div>
</section>

<section class="content">
<div class="container-fluid">

<!-- ================= TAB ================= -->
<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link"
       href="<?= base_url('admin/rekap-absensi') ?>?start=<?= esc($start) ?>&end=<?= esc($end) ?>">
      📅 Per Tanggal
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link active"
       href="<?= base_url('admin/rekap-absensi/kelas') ?>?start=<?= esc($start) ?>&end=<?= esc($end) ?>">
      🏫 Per Kelas
    </a>
  </li>
</ul>

<!-- ================= FILTER ================= -->
<form method="get" class="row mb-3">
  <div class="col-4 col-md-3 mb-2">
    <label class="small">Dari</label>
    <input type="date"
           name="start"
           value="<?= esc($start ?? '') ?>"
           class="form-control form-control-sm"
           required>
  </div>

  <div class="col-4 col-md-3 mb-2">
    <label class="small">Sampai</label>
    <input type="date"
           name="end"
           value="<?= esc($end ?? '') ?>"
           class="form-control form-control-sm"
           required>
  </div>

  <div class="col-4 col-md-2 mb-2">
    <label class="small">Kelas</label>
    <select name="kelas" class="form-control form-control-sm">
      <option value="">Semua</option>
      <?php foreach ($mapKelas as $id=>$nama): ?>
        <option value="<?= $id ?>" <?= ($kelasAktif==$id)?'selected':'' ?>>
          <?= esc($nama) ?>
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

<hr>

<!-- ================= HASIL ================= -->
<?php if (empty($rows)): ?>
  <div class="text-center text-muted py-4">
    Tidak ada data presensi
  </div>
<?php endif; ?>

<div class="row">
<?php foreach ($rows as $r): ?>
  <?php
    $kelasId   = $r['kelas_id'];
    $namaKelas = $mapKelas[$kelasId] ?? 'ID '.$kelasId;
  ?>

  <div class="col-md-4 mb-3">
    <div class="card h-100">
      <div class="card-body">

        <h5 class="mb-1">
          🏫 Kelas <?= esc($namaKelas) ?>
        </h5>

        <div class="text-muted small mb-2">
          👥 <?= (int)$r['total_hadir'] ?> siswa hadir<br>
          📆 <?= (int)$r['total_hari'] ?> hari<br>
          👨‍🏫 <?= (int)$r['total_guru'] ?> guru
        </div>

        <a href="<?= base_url('admin/rekap-absensi/kelas-detail') ?>
?kelas=<?= esc($r['kelas_id']) ?>
&start=<?= esc($start) ?>
&end=<?= esc($end) ?>"
class="btn btn-sm btn-outline-primary">
  📂 Detail
</a>

      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

</div>
</section>

@endsection
