@extends('layouts/adminlte')
@section('content')

<section class="content-header">
  <div class="container-fluid">
    <h1>
      Monitoring Sistem
      <small class="text-muted">Pantau aktivitas Admin dan Guru</small>
    </h1>
  </div>
</section>

<section class="content">
<div class="container-fluid">

<?php if (!empty($warning)): ?>
  <div class="alert alert-warning"><?= esc($warning) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-header bg-secondary text-white">
    Monitoring Admin
  </div>
  <div class="card-body table-responsive">

    <table class="table table-striped table-hover">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Last Seen</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($admin as $a): ?>
        <?php $onlineAdmin = !empty($a['last_seen']) && (time() - strtotime($a['last_seen']) <= 300); ?>
        <tr>
          <td><?= esc($a['nama_depan'].' '.$a['nama_belakang']) ?></td>
          <td><?= esc($a['last_seen'] ?? '-') ?></td>
          <td>
            <?php if($onlineAdmin): ?>
              <span class="badge badge-success">Online</span>
            <?php else: ?>
              <span class="badge badge-secondary">Offline</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>

  </div>
</div>

<div class="card shadow-sm">
  <div class="card-header bg-primary text-white">
    Monitoring Guru
  </div>
  <div class="card-body table-responsive">

    <table class="table table-striped table-hover">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Last Seen</th>
          <th>Status</th>
          <th>Total Absensi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($guru as $g): ?>
        <tr>
          <td><?= esc($g['nama_depan'].' '.$g['nama_belakang']) ?></td>
          <td><?= esc($g['last_seen'] ?? '-') ?></td>
          <td>
            <?php if($g['online']): ?>
              <span class="badge badge-success">Online</span>
            <?php else: ?>
              <span class="badge badge-secondary">Offline</span>
            <?php endif; ?>
          </td>
          <td><?= esc($g['total_absen'] ?? 0) ?></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>

  </div>
</div>

</div>
</section>

@endsection
