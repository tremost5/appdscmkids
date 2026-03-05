@extends('layouts/adminlte')
@section('content')

<?php
$mapKelas = [
  1=>'PG',2=>'TKA',3=>'TKB',
  4=>'1',5=>'2',6=>'3',
  7=>'4',8=>'5',9=>'6',11=>'TR'
];

$mapLokasi = [
  1=>'NICC',2=>'GRASA',3=>'CPM',4=>'Online'
];
?>

<section class="content-header">
  <div class="container-fluid">
    <h1>
      Rekap Presensi Kelas <?= esc($mapKelas[$kelas] ?? '-') ?>
    </h1>
    <p class="text-muted">
      Periode <?= esc($start) ?> s/d <?= esc($end) ?>
    </p>
  </div>
</section>

<section class="content">
<div class="container-fluid">

<!-- ================= FILTER ================= -->
<form method="get" class="row mb-3">
  <input type="hidden" name="kelas" value="<?= esc($kelas) ?>">
  <input type="hidden" name="start" value="<?= esc($start) ?>">
  <input type="hidden" name="end" value="<?= esc($end) ?>">
  <input type="hidden" name="mode" value="<?= esc($mode ?? 'all') ?>">

  <div class="col-md-3 mb-2">
    <label>Guru</label>
    <select name="guru" class="form-control form-control-sm">
      <option value="">Semua Guru</option>
      <?php foreach ($guruList as $g): ?>
        <option value="<?= $g['id'] ?>" <?= ($guru==$g['id'])?'selected':'' ?>>
          <?= esc($g['nama_depan'].' '.$g['nama_belakang']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-3 mb-2">
    <label>Lokasi</label>
    <select name="lokasi" class="form-control form-control-sm">
      <option value="">Semua Lokasi</option>
      <?php foreach ($mapLokasi as $id=>$nama): ?>
        <option value="<?= $id ?>" <?= ($lokasi==$id)?'selected':'' ?>>
          <?= esc($nama) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-3 mb-2">
    <label>Unity</label>
    <select name="unity" class="form-control form-control-sm">
      <option value="">Semua Unity</option>
      <?php foreach (array_keys(unityMetaMap()) as $u): ?>
        <option value="<?= esc($u) ?>" <?= (($unity ?? '')===$u)?'selected':'' ?>>
          <?= esc($u) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-2 mb-2 d-flex align-items-end">
    <button class="btn btn-primary btn-sm btn-block">
      🔍 Filter
    </button>
  </div>
</form>

<hr>

<?php if (empty($rows)): ?>
  <div class="text-center text-muted py-4">
    Tidak ada data presensi
  </div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-bordered table-sm">
  <thead class="thead-light">
    <tr>
      <th>Tanggal</th>
      <th>Nama Siswa</th>
      <th>Unity</th>
      <th>Jam</th>
      <th>Lokasi</th>
      <th>Guru</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= esc($r['tanggal']) ?></td>
      <td><?= esc($r['nama_depan'].' '.$r['nama_belakang']) ?></td>
      <td><?= unityBadge($r['unity'] ?? '') ?> <?= esc($r['unity'] ?? '-') ?></td>
      <td><?= esc($r['jam']) ?></td>
      <td><?= esc($r['nama_lokasi'] ?? '-') ?></td>
    <td><?= esc(trim(($r['guru_depan'] ?? '').' '.($r['guru_belakang'] ?? ''))) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<a href="<?= base_url('admin/rekap-absensi/kelas') ?>?start=<?= esc($start) ?>&end=<?= esc($end) ?>&unity=<?= esc($unity ?? '') ?>&mode=<?= esc($mode ?? 'all') ?>"
   class="btn btn-secondary btn-sm mt-3">
  ⬅️ Kembali
</a>

</div>
</section>

@endsection
