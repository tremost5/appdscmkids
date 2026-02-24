@extends('layouts/adminlte')
@section('content')

<?php
$tanggal = $tanggal ?? date('Y-m-d');
$lokasi  = $lokasi  ?? '';
$kelas   = $kelas   ?? '';
$guru    = $guru    ?? '';

$mapKelas = [
    1=>'PG',2=>'TKA',3=>'TKB',
    4=>'1',5=>'2',6=>'3',
    7=>'4',8=>'5',9=>'6'
];

$mapLokasi = [
    1=>'NICC',2=>'GRASA',3=>'CPM'
];

/* GROUP DATA PER KELAS */
$grouped = [];
foreach ($data as $d) {
    $grouped[$d['kelas_id']][] = $d;
}

/* helper lokasi aman */
function lokasiLabel($id, $map) {
    if (!$id) return '-';
    return $map[$id] ?? 'ID:' . $id;
}
?>

<style>
.kelas-header{
  background:#f1f5f9;
  border-left:6px solid #0d6efd;
  padding:8px 12px;
  font-weight:700;
  margin-top:18px;
  border-radius:6px;
}
.absensi-card{
  border:1px solid #e5e7eb;
  border-radius:10px;
  padding:10px 12px;
  margin-bottom:10px;
}
.absensi-meta{
  font-size:13px;
  color:#555;
}
.badge-kelas{
  background:#0d6efd;
  color:#fff;
  font-size:11px;
  padding:3px 7px;
  border-radius:6px;
  margin-left:6px;
}
@media (min-width:768px){
  .absensi-card{display:none;}
}
@media (max-width:767px){
  table{display:none;}
}
</style>

<section class="content-header">
  <div class="container-fluid">
    <h1>Rekap Presensi</h1>
    <p class="text-muted">Data kehadiran siswa</p>
  </div>
</section>

<section class="content">
<div class="container-fluid">

<!-- FILTER -->
<form method="get" class="row mb-3">
  <div class="col-md-3 mb-2">
    <label>Tanggal</label>
    <input type="date" name="tanggal" value="<?= esc($tanggal) ?>" class="form-control">
  </div>

  <div class="col-md-3 mb-2">
    <label>Lokasi</label>
    <select name="lokasi" class="form-control">
      <option value="">Semua Lokasi</option>
      <?php foreach ($mapLokasi as $id=>$nama): ?>
        <option value="<?= $id ?>" <?= ($lokasi==$id)?'selected':'' ?>>
          <?= esc($nama) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-2 mb-2">
    <label>Kelas</label>
    <select name="kelas" class="form-control">
      <option value="">Semua</option>
      <?php foreach ($mapKelas as $id=>$nama): ?>
        <option value="<?= $id ?>" <?= ($kelas==$id)?'selected':'' ?>>
          <?= esc($nama) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-3 mb-2">
    <label>Guru</label>
    <select name="guru" class="form-control">
      <option value="">Semua Guru</option>
      <?php foreach ($guruList ?? [] as $g): ?>
        <option value="<?= $g['id'] ?>" <?= ($guru==$g['id'])?'selected':'' ?>>
          <?= esc($g['nama_depan'].' '.$g['nama_belakang']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-1 mb-2 d-flex align-items-end">
    <button class="btn btn-primary btn-block">🔍</button>
  </div>
</form>

<?php if (empty($grouped)): ?>
  <div class="text-center text-muted py-4">
    Tidak ada data presensi
  </div>
<?php endif; ?>

<?php foreach ($grouped as $kelasId => $rows): ?>
  <div class="kelas-header">
    Kelas <?= esc($mapKelas[$kelasId] ?? '-') ?> (<?= count($rows) ?> siswa)
  </div>

  <!-- MOBILE -->
  <?php foreach ($rows as $d): ?>
  <div class="absensi-card d-md-none">
    <strong>
      <?= esc($d['nama_depan'].' '.$d['nama_belakang']) ?>
      <span class="badge-kelas">
        <?= esc($mapKelas[$d['kelas_id']] ?? '-') ?>
      </span>
    </strong>
    <div class="absensi-meta">
      📍 <?= esc(lokasiLabel($d['lokasi_id'], $mapLokasi)) ?><br>
      🕒 <?= esc($d['jam']) ?><br>
      👨‍🏫 <?= esc(trim(($d['guru_depan'] ?? '').' '.($d['guru_belakang'] ?? ''))) ?: '-' ?>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- DESKTOP -->
  <div class="table-responsive d-none d-md-block">
    <table class="table table-bordered table-sm">
      <thead class="thead-light">
        <tr>
          <th>Nama</th>
          <th>Kelas</th>
          <th>Status</th>
          <th>Lokasi</th>
          <th>Jam</th>
          <th>Guru</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $d): ?>
        <tr>
          <td><?= esc($d['nama_depan'].' '.$d['nama_belakang']) ?></td>
          <td><?= esc($mapKelas[$d['kelas_id']] ?? '-') ?></td>
          <td><span class="badge badge-success">HADIR</span></td>
          <td><?= esc(lokasiLabel($d['lokasi_id'], $mapLokasi)) ?></td>
          <td><?= esc($d['jam']) ?></td>
          <td><?= esc(trim(($d['guru_depan'] ?? '').' '.($d['guru_belakang'] ?? ''))) ?: '-' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php endforeach; ?>

</div>
</section>

@endsection
