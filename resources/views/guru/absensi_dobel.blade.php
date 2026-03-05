@extends('layouts/adminlte')
@section('content')

<style>
.badge-dobel{background:#fde68a;color:#92400e}
.card-glass{
  background:rgba(255,255,255,.85);
  backdrop-filter:blur(6px);
  border-radius:14px
}
</style>

<a href="https://wa.me/62XXXXXXXXXX?text=Halo%20Admin%2C%20ada%20presensi%20dobel"
   class="btn btn-success btn-sm">
📲 Hubungi Admin
</a>

<div class="alert alert-warning">
⚠️ Data ini <b>tidak perlu diperbaiki guru</b>.<br>
Silakan lanjut mengajar, admin akan menyelesaikan.
</div>

<div class="card mb-3 shadow-sm card-glass">
  <div class="card-body d-flex align-items-center"
       style="background:linear-gradient(90deg,#f59e0b,#fbbf24);color:#78350f">
    <div>
      <h4 class="mb-0">⚠️ Detail Presensi Dobel</h4>
      <small>Murid sudah dipresensi oleh guru lain</small>
    </div>
  </div>
</div>

<?php if(empty($data)): ?>
<div class="alert alert-success">✅ Tidak ada presensi dobel hari ini.</div>
<?php else: ?>

<?php
$kelasMap=[1=>'PG',2=>'TKA',3=>'TKB',4=>'1',5=>'2',6=>'3',7=>'4',8=>'5',9=>'6',11=>'TR'];
?>

<div class="card shadow-sm card-glass">
<div class="card-body p-0">

<div class="table-responsive">
<table class="table table-hover mb-0">
<thead class="thead-dark">
<tr>
  <th>Nama Murid</th>
  <th>Kelas</th>
  <th>Tanggal</th>
  <th>Jam</th>
  <th>Lokasi</th>
  <th>Guru Sebelumnya</th>
</tr>
</thead>
<tbody>

<?php foreach($data as $d): ?>
<?php
  $namaLengkap = trim(($d['nama_depan'] ?? '').' '.($d['nama_belakang'] ?? ''));
  $panggilan   = $d['panggilan'] ?? '';

  $displayNama = $panggilan
    ? $panggilan.' ('.$namaLengkap.')'
    : $namaLengkap;
?>
<tr>
  <td><?= esc($displayNama) ?></td>
  <td><span class="badge badge-info"><?= $kelasMap[$d['kelas_id']] ?></span></td>
  <td><?= esc($d['tanggal']) ?></td>
  <td><?= esc($d['jam']) ?></td>
  <td><?= esc($d['lokasi_id']) ?></td>
  <td>
    <span class="badge badge-dobel">
      <?= esc(trim($d['guru_depan'].' '.$d['guru_belakang'])) ?>
    </span>
  </td>
</tr>
<?php endforeach ?>

</tbody>
</table>
</div>
</div>
</div>
<?php endif ?>

<div class="mt-3 text-center">
  <a href="<?= base_url('guru/absensi') ?>" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
  <a href="<?= base_url('dashboard/guru') ?>" class="btn btn-outline-secondary btn-sm">🏠 Dashboard</a>
</div>

@endsection
