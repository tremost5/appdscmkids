@extends('layouts/adminlte')
@section('content')

<h4>System Emergency Control</h4>

<div class="card mb-3">
<div class="card-body">

<h5>Maintenance Mode</h5>
<?php if($maintenance): ?>
<span class="badge badge-danger">ACTIVE</span>
<?php else: ?>
<span class="badge badge-success">OFF</span>
<?php endif ?>
<br><br>
<a href="<?= base_url('superadmin/system-control/toggle-maintenance') ?>"
   class="btn btn-warning">Toggle Maintenance</a>

<hr>

<h5>Presensi Lock</h5>
<?php if($absensi_lock): ?>
<span class="badge badge-danger">LOCKED</span>
<?php else: ?>
<span class="badge badge-success">OPEN</span>
<?php endif ?>
<br><br>
<a href="<?= base_url('superadmin/system-control/toggle-absensi') ?>"
   class="btn btn-danger">Toggle Presensi</a>

</div>
</div>

<div class="card mb-3">
<div class="card-body">
<h5>Kontrol Menu Admin & Guru</h5>
<p class="text-muted">Superadmin dapat menyalakan/mematikan fitur per menu.</p>

<table class="table table-sm table-bordered">
  <thead>
    <tr>
      <th>Menu</th>
      <th width="120">Status</th>
      <th width="160">Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($menuSettings as $key => $item): ?>
      <tr>
        <td><?= esc($item['label']) ?></td>
        <td>
          <?php if ((int) $item['value'] === 1): ?>
            <span class="badge badge-success">ON</span>
          <?php else: ?>
            <span class="badge badge-secondary">OFF</span>
          <?php endif; ?>
        </td>
        <td>
          <form method="post" action="<?= base_url('superadmin/system-control/toggle-menu/'.$key) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-primary w-100">Toggle</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>

@endsection
