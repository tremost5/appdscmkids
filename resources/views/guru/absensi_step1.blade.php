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
.section-head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  margin-bottom:12px;
}
.clock-badge{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:10px 14px;
  border-radius:999px;
  background:linear-gradient(90deg,#111827,#7c3aed);
  color:#fff;
  font-weight:800;
  letter-spacing:.08em;
  box-shadow:0 10px 24px rgba(124,58,237,.22);
}
.clock-badge small{
  letter-spacing:normal;
  opacity:.75;
  font-weight:600;
}
@media(max-width:768px){
  .kelas-list{gap:6px}
  .section-head{
    flex-direction:column;
    align-items:flex-start;
  }
}
</style>

<div class="card mb-3 shadow-sm card-glass">
  <div class="card-body absensi-hero">
    <h4 class="mb-1">Presensi Sekolah Minggu</h4>
    <small>Pilih kelas dan lokasi sebelum melanjutkan.</small>
  </div>
</div>

<div class="card shadow-sm card-glass">
<div class="card-body">

<form method="get"
      action="<?= base_url('guru/absensi/tampilkan') ?>"
      onsubmit="return validateForm()">

<input type="hidden" name="jam_input" id="jam_input" value="<?= esc($currentTime ?? date('H:i:s')) ?>">

<div class="mb-4">
  <div class="section-head">
    <strong class="d-block mb-0">Pilih Kelas</strong>
    <span class="clock-badge">
      <small>Jam Presensi</small>
      <span id="clockDisplay"><?= esc(substr((string) ($currentTime ?? date('H:i:s')), 0, 5)) ?></span>
    </span>
  </div>
  <div class="kelas-list">
    <?php foreach (($kelasGroups ?? []) as $key => $grp): ?>
      <label>
        <input type="checkbox" name="kelas[]" value="<?= esc($key) ?>"> <?= esc($grp['label'] ?? $key) ?>
      </label>
    <?php endforeach; ?>
  </div>
</div>

<div class="mb-4">
  <strong class="d-block mb-2">Lokasi</strong>
  <select name="lokasi" id="lokasi" class="form-control">
    <option value="">-- pilih lokasi --</option>
    <?php foreach (($lokasiOptions ?? []) as $l): ?>
      <option value="<?= (int) $l['id'] ?>"><?= esc($l['nama_lokasi']) ?></option>
    <?php endforeach; ?>
  </select>
</div>

<button class="btn btn-absensi-main btn-lg btn-block">
  Lanjut Presensi
</button>

<a href="<?= base_url('dashboard/guru') ?>"
   class="btn btn-outline-secondary btn-block mt-2">
  Kembali
</a>

</form>
</div>
</div>

<script>
const lokasiSelect = document.getElementById('lokasi');
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

function validateForm(){
  if (!document.querySelector('input[name="kelas[]"]:checked') || !lokasiSelect.value) {
    alert('Kelas dan lokasi wajib dipilih');
    return false;
  }

  return true;
}

syncClock();
setInterval(syncClock, 1000);
</script>

@endsection
