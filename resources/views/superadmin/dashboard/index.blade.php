@extends('layouts/adminlte')
@section('content')

<style>
.super-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
  gap:14px;
}
.card-stat{
  padding:16px;
  border-radius:14px;
  color:#fff;
  position:relative;
}
.card-stat i{
  position:absolute;
  right:14px;
  bottom:10px;
  font-size:36px;
  opacity:.25;
}
.bg-blue{background:linear-gradient(135deg,#2563eb,#60a5fa)}
.bg-green{background:linear-gradient(135deg,#16a34a,#22c55e)}
.bg-orange{background:linear-gradient(135deg,#f97316,#fb923c)}
.bg-red{background:linear-gradient(135deg,#dc2626,#f87171)}
</style>

<h3 class="mb-3">🛡️ Superadmin Control Center</h3>

<div class="super-grid mb-4">
  <div class="card-stat bg-blue">
    <h3><?= $total_users ?></h3>
    <p>Total User</p>
    <i class="fas fa-users"></i>
  </div>
  <div class="card-stat bg-green">
    <h3><?= $user_online ?></h3>
    <p>User Online</p>
    <i class="fas fa-signal"></i>
  </div>
  <div class="card-stat bg-orange">
    <h3><?= $absen_hari_ini ?></h3>
    <p>Presensi Hari Ini</p>
    <i class="fas fa-clipboard-check"></i>
  </div>
  <div class="card-stat bg-red">
    <h3><?= $absen_dobel ?></h3>
    <p>Presensi Dobel</p>
    <i class="fas fa-exclamation-triangle"></i>
  </div>
</div>

<div class="card">
  <div class="card-header bg-dark text-white">
    📜 Aktivitas Sistem
  </div>
  <div class="card-body" style="max-height:260px;overflow:auto">
    <?php foreach($activity as $a): ?>
      <div style="border-bottom:1px solid #eee;padding:6px 0">
        <strong><?= esc($a['aksi']) ?></strong><br>
        <small class="text-muted">
          <?= esc($a['deskripsi']) ?> · <?= $a['created_at'] ?>
        </small>
      </div>
    <?php endforeach ?>
  </div>
</div>

@endsection
