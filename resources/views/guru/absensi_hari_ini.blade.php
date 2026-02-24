@extends('layouts/adminlte')
@section('content')

<style>
.guru-pill-primary{background:linear-gradient(90deg,#7c3aed,#ec4899)!important;border:none!important;color:#fff!important}
.guru-text-primary{color:#7c3aed!important}
.guru-badge-hadir{background:#9333ea!important;color:#fff!important}
.guru-header-soft{background:linear-gradient(90deg,#f3e8ff,#fce7f3)!important}
</style>

<h3 class="mb-3">Presensi Hari Ini</h3>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success">
    <?= esc(session()->getFlashdata('success')) ?>
  </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger">
    <?= esc(session()->getFlashdata('error')) ?>
  </div>
<?php endif; ?>

<?php if (!$absensi): ?>
  <div class="alert alert-info">Belum ada presensi hari ini.</div>
  <a href="<?= base_url('dashboard/guru') ?>" class="btn btn-secondary">Dashboard</a>
  <a href="<?= base_url('guru/absensi') ?>" class="btn btn-primary">Presensi</a>
<?php else: ?>
<?php
$hadir = 0;
$dobel = 0;
foreach ($detail as $d) {
  if (($d['status'] ?? '') === 'hadir') $hadir++;
  if (($d['status'] ?? '') === 'dobel') $dobel++;
}

$refTime = !empty($absensi['created_at'])
  ? strtotime($absensi['created_at'])
  : strtotime(($absensi['tanggal'] ?? date('Y-m-d')).' '.($absensi['jam'] ?? date('H:i:s')));
$sisa = max(0, 10800 - (time() - ($refTime ?: time())));
?>

<?php if ($sisa > 0): ?>
<div class="alert alert-info" id="countdownBox">
  Waktu edit tersisa:
  <strong><span id="countdown"><?= gmdate('H:i:s', $sisa) ?></span></strong>
</div>
<?php endif; ?>

<div class="card mb-3 shadow-sm">
  <div class="card-body d-flex justify-content-between align-items-center">
    <div>
      <strong><?= date('d M Y', strtotime($absensi['tanggal'])) ?></strong><br>
      <small class="text-muted">
        <?= substr($absensi['jam'] ?? '00:00:00', 0, 5) ?> - <?= esc($absensi['lokasi_text'] ?? '-') ?>
      </small>
    </div>
    <span class="badge bg-primary">CPM</span>
  </div>
</div>

<div class="row mb-3">
  <div class="col-md-6 mb-2">
    <div class="card shadow-sm text-center">
      <div class="card-body">
        <div class="text-success fw-bold">Hadir</div>
        <div class="fs-2"><?= $hadir ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-6 mb-2">
    <div class="card shadow-sm text-center">
      <div class="card-body">
        <div class="text-warning fw-bold">Dobel</div>
        <div class="fs-2"><?= $dobel ?></div>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($absensi['selfie_foto'])): ?>
<div class="card mb-3 shadow-sm text-center">
  <div class="card-body">
    <img src="<?= base_url('uploads/selfie/'.$absensi['selfie_foto']) ?>"
         class="img-fluid rounded shadow-sm mb-2"
         style="max-height:240px">
    <div class="text-muted small">Selfie Guru</div>
  </div>
</div>
<?php endif; ?>

<form method="post" action="<?= base_url('guru/absensi-hari-ini/simpan') ?>">
<?= csrf_field() ?>
<input type="hidden" name="absensi_id" value="<?= (int) $absensi['id'] ?>">

<table class="table table-bordered table-striped align-middle">
<thead class="guru-header-soft">
<tr>
  <th>Nama Murid</th>
  <th>Kelas</th>
  <th>Status</th>
  <th>Aksi</th>
</tr>
</thead>
<tbody>
<?php
$label = [1=>'PG',2=>'TKA',3=>'TKB',4=>'1',5=>'2',6=>'3',7=>'4',8=>'5',9=>'6'];
foreach($detail as $d):
  $namaLengkap = trim(($d['nama_depan'] ?? '').' '.($d['nama_belakang'] ?? ''));
  $panggilan   = $d['panggilan'] ?? '';
  $displayNama = $panggilan ? $panggilan.' ('.$namaLengkap.')' : $namaLengkap;
  $kelasLabel  = $label[(int)($d['kelas_id'] ?? 0)] ?? ($d['nama_kelas'] ?? '-');
?>
<tr class="<?= ($d['status'] ?? '')==='dobel' ? 'table-warning' : '' ?>">
<td>
  <span class="guru-text-primary" style="cursor:pointer"
        onclick="showFoto('<?= base_url('uploads/murid/'.($d['foto'] ?? 'default_murid.png')) ?>')">
    <?= esc($displayNama) ?>
  </span>
</td>
<td><?= esc($kelasLabel) ?></td>
<td>
  <?php if(($d['status'] ?? '')==='hadir'): ?>
    <span class="badge guru-badge-hadir">Hadir</span>
  <?php else: ?>
    <span class="badge bg-warning text-dark">Dobel</span>
  <?php endif; ?>
</td>
<td>
  <input type="checkbox"
         name="hadir[]"
         value="<?= (int)($d['murid_id'] ?? 0) ?>"
         <?= ($d['status'] ?? '')==='hadir' ? 'checked' : '' ?>
         <?= $sisa > 0 ? '' : 'disabled' ?>>
  <?php if ($sisa <= 0): ?>
    <small class="text-muted d-block">Window edit habis</small>
  <?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<button class="btn guru-pill-primary mb-3" <?= $sisa > 0 ? '' : 'disabled' ?>>
  Simpan Perubahan
</button>
<?php if ($sisa <= 0): ?>
  <div class="alert alert-warning py-2">
    Batas edit 3 jam sudah berakhir.
  </div>
<?php endif; ?>
</form>

<a href="<?= base_url('dashboard/guru') ?>" class="btn btn-secondary">Dashboard</a>
<a href="<?= base_url('guru/absensi') ?>" class="btn btn-primary">Presensi</a>

<div id="fotoOverlay"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:1050"
     onclick="this.style.display='none'">
  <img id="fotoPreview"
       style="max-width:85vw;max-height:85vh;margin:auto;display:block">
</div>

<script>
function showFoto(src){
  if(!src || src.endsWith('/')){
    alert('Foto murid belum tersedia');
    return;
  }
  document.getElementById('fotoPreview').src = src;
  document.getElementById('fotoOverlay').style.display = 'flex';
}
</script>

<script>
<?php if($sisa > 0): ?>
let sisa = <?= (int) $sisa ?>;
const el = document.getElementById('countdown');
if (el) {
  setInterval(() => {
    sisa--;
    if (sisa <= 0) location.reload();
    const h = String(Math.floor(sisa/3600)).padStart(2,'0');
    const m = String(Math.floor((sisa%3600)/60)).padStart(2,'0');
    const d = String(sisa%60).padStart(2,'0');
    el.innerText = `${h}:${m}:${d}`;
  }, 1000);
}
<?php endif; ?>
</script>
<?php endif; ?>

@endsection
