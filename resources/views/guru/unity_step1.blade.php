@extends('layouts/adminlte')
@section('content')

<style>
.card-glass{background:rgba(255,255,255,.95);backdrop-filter:blur(6px);border-radius:14px}
.kelas-list{display:flex;flex-wrap:wrap;gap:10px}
.kelas-list label{background:#f1f5f9;padding:8px 14px;border-radius:999px;cursor:pointer}
.kelas-list input{margin-right:6px}
.unity-list{display:flex;flex-wrap:wrap;gap:10px}
.unity-list label{padding:8px 12px;border-radius:10px;border:1px solid #e2e8f0;cursor:pointer}
.unity-list input{margin-right:6px}
.unity-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px}
.hero{background:linear-gradient(90deg,#0ea5e9,#16a34a);color:#fff}
.btn-main{background:linear-gradient(90deg,#0ea5e9,#16a34a);border:none;color:#fff;font-weight:700}
.btn-main:hover{filter:brightness(1.05);color:#fff}
</style>

<div class="card mb-3 shadow-sm card-glass">
  <div class="card-body hero">
    <h4 class="mb-1">⭐ Presensi Unity</h4>
    <small>Pilih unity, kelas (opsional), dan lokasi</small>
  </div>
</div>

<div class="card shadow-sm card-glass">
<div class="card-body">

<form method="get" action="<?= base_url('guru/unity/tampilkan') ?>" onsubmit="return validateForm()">
<div class="mb-4">
  <strong class="d-block mb-2">Pilih Unity</strong>
  <div class="unity-list">
    <?php foreach (($unityMap ?? []) as $unity => $meta): ?>
      <label>
        <input type="radio" name="unity" value="<?= esc($unity) ?>">
        <span class="unity-dot" style="background:<?= esc($meta['color']) ?>"></span><?= esc($unity) ?>
      </label>
    <?php endforeach; ?>
  </div>
</div>

<div class="mb-4">
  <strong class="d-block mb-2">Pilih Kelas (Opsional)</strong>
  <div class="kelas-list">
    <?php foreach (($kelasGroups ?? []) as $key => $grp): ?>
      <label>
        <input type="checkbox" name="kelas[]" value="<?= esc($key) ?>"> <?= esc($grp['label'] ?? $key) ?>
      </label>
    <?php endforeach; ?>
  </div>
  <small class="text-muted">Jika tidak dipilih, semua kelas ditampilkan.</small>
</div>

<div class="mb-4">
  <strong class="d-block mb-2">Lokasi</strong>
  <select name="lokasi" id="lokasi" class="form-control">
    <option value="">-- pilih lokasi --</option>
    <option value="1">NICC</option>
    <option value="2">GRASA</option>
    <option value="3">CPM</option>
  </select>
</div>

<button class="btn btn-main btn-lg btn-block">➡️ Lanjut Presensi Unity</button>
<a href="<?= base_url('dashboard/guru') ?>" class="btn btn-outline-secondary btn-block mt-2">❌ Kembali</a>
</form>

</div>
</div>

<script>
function validateForm(){
  if(!document.querySelector('input[name="unity"]:checked') || !document.getElementById('lokasi').value){
    alert('Unity dan lokasi wajib dipilih');
    return false;
  }
  return true;
}
</script>

@endsection
