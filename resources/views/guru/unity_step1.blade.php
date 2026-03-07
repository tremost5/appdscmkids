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
.section-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}
.clock-badge{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:10px 14px;
  border-radius:999px;
  background:linear-gradient(90deg,#083344,#0f766e);
  color:#fff;
  font-weight:800;
  letter-spacing:.08em;
  box-shadow:0 10px 24px rgba(15,118,110,.22);
}
.clock-badge small{letter-spacing:normal;opacity:.75;font-weight:600}
.custom-location{display:none;margin-top:10px}
@media(max-width:768px){
  .section-head{flex-direction:column;align-items:flex-start}
}
</style>

<div class="card mb-3 shadow-sm card-glass">
  <div class="card-body hero">
    <h4 class="mb-1">Presensi Unity</h4>
    <small>Pilih unity, kelas opsional, dan lokasi.</small>
  </div>
</div>

<div class="card shadow-sm card-glass">
<div class="card-body">

<form method="get" action="<?= base_url('guru/unity/tampilkan') ?>" onsubmit="return validateForm()">
<input type="hidden" name="jam_input" id="jam_input" value="<?= esc($currentTime ?? date('H:i:s')) ?>">

<div class="mb-4">
  <div class="section-head">
    <strong class="d-block mb-0">Pilih Unity</strong>
    <span class="clock-badge">
      <small>Jam Presensi</small>
      <span id="clockDisplay"><?= esc(substr((string) ($currentTime ?? date('H:i:s')), 0, 5)) ?></span>
    </span>
  </div>
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
    <?php foreach (($lokasiOptions ?? []) as $l): ?>
      <option value="<?= (int) $l['id'] ?>" data-is-other="<?= strtolower((string) $l['nama_lokasi']) === 'lokasi lainnya' ? '1' : '0' ?>">
        <?= esc($l['nama_lokasi']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <input type="text"
         name="lokasi_lainnya"
         id="lokasi_lainnya"
         class="form-control custom-location"
         placeholder="Tulis lokasi di luar daftar">
</div>

<button class="btn btn-main btn-lg btn-block">Lanjut Presensi Unity</button>
<a href="<?= base_url('dashboard/guru') ?>" class="btn btn-outline-secondary btn-block mt-2">Kembali</a>
</form>

</div>
</div>

<script>
const lokasiSelect = document.getElementById('lokasi');
const lokasiLainnya = document.getElementById('lokasi_lainnya');
const jamInput = document.getElementById('jam_input');
const clockDisplay = document.getElementById('clockDisplay');

function syncClock() {
  const now = new Date();
  const hh = String(now.getHours()).padStart(2, '0');
  const mm = String(now.getMinutes()).padStart(2, '0');
  const ss = String(now.getSeconds()).padStart(2, '0');
  jamInput.value = `${hh}:${mm}:${ss}`;
  clockDisplay.textContent = `${hh}:${mm}`;
}

function toggleLokasiLainnya() {
  const selectedOption = lokasiSelect.options[lokasiSelect.selectedIndex];
  const show = selectedOption && selectedOption.dataset.isOther === '1';
  lokasiLainnya.style.display = show ? 'block' : 'none';
  lokasiLainnya.required = show;
  if (!show) {
    lokasiLainnya.value = '';
  }
}

function validateForm(){
  if (!document.querySelector('input[name="unity"]:checked') || !lokasiSelect.value) {
    alert('Unity dan lokasi wajib dipilih');
    return false;
  }

  const selectedOption = lokasiSelect.options[lokasiSelect.selectedIndex];
  if (selectedOption && selectedOption.dataset.isOther === '1' && !lokasiLainnya.value.trim()) {
    alert('Tulis lokasi lainnya terlebih dahulu');
    return false;
  }

  return true;
}

syncClock();
setInterval(syncClock, 1000);
lokasiSelect.addEventListener('change', toggleLokasiLainnya);
toggleLokasiLainnya();
</script>

@endsection
