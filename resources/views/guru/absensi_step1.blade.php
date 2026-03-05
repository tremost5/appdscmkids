@extends('layouts/adminlte')
@section('content')

<style>
.card-glass{
  background:rgba(255,255,255,.95);
  backdrop-filter:blur(6px);
  border-radius:14px;
}
.kelas-list{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
}
.kelas-list label{
  background:#f1f5f9;
  padding:8px 14px;
  border-radius:999px;
  cursor:pointer;
}
.kelas-list input{margin-right:6px}
.absensi-hero{
  background:linear-gradient(90deg,#7c3aed,#ec4899);
  color:#fff;
}
.btn-absensi-main{
  background:linear-gradient(90deg,#7c3aed,#ec4899);
  border:none;
  color:#fff;
  font-weight:700;
}
.btn-absensi-main:hover{
  filter:brightness(1.05);
  color:#fff;
}
@media(max-width:768px){
  .kelas-list{gap:6px}
}
</style>

<div class="card mb-3 shadow-sm card-glass">
  <div class="card-body absensi-hero">
    <h4 class="mb-1">📋 Presensi Sekolah Minggu</h4>
    <small>Pilih kelas & lokasi sebelum melanjutkan</small>
  </div>
</div>

<div class="card shadow-sm card-glass">
<div class="card-body">

<form method="get"
      action="<?= base_url('guru/absensi/tampilkan') ?>"
      onsubmit="return validateForm()">

<!-- ================== PILIH KELAS ================== -->
<div class="mb-4">
  <strong class="d-block mb-2">Pilih Kelas</strong>
  <div class="kelas-list">
    <?php foreach (($kelasGroups ?? []) as $key => $grp): ?>
      <label>
        <input type="checkbox" name="kelas[]" value="<?= esc($key) ?>"> <?= esc($grp['label'] ?? $key) ?>
      </label>
    <?php endforeach; ?>
  </div>
</div>

<!-- ================== PILIH LOKASI (FIX INTEGER) ================== -->
<div class="mb-4">
  <strong class="d-block mb-2">Lokasi</strong>
  <select name="lokasi" id="lokasi" class="form-control">
    <option value="">-- pilih lokasi --</option>
    <option value="1">NICC</option>
    <option value="2">GRASA</option>
    <option value="3">CPM</option>
  </select>
</div>

<button class="btn btn-absensi-main btn-lg btn-block">
  ➡️ Lanjut Presensi
</button>

<a href="<?= base_url('dashboard/guru') ?>"
   class="btn btn-outline-secondary btn-block mt-2">
  ❌ Kembali
</a>

</form>
</div>
</div>

<script>
function validateForm(){
  if(!document.querySelector('input[name="kelas[]"]:checked') ||
     !document.getElementById('lokasi').value){
    alert('Kelas dan lokasi wajib dipilih');
    return false;
  }
  return true;
}
</script>

@endsection
