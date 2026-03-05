@extends('layouts/adminlte')
@section('content')

<section class="content-header">
  <h3>Data Murid</h3>
</section>

<section class="content">
  <div class="card">
    <div class="card-body">
      <form method="get" class="row mb-3">
        <div class="col-md-4 mb-2">
          <input type="text" name="q" value="<?= esc($q ?? '') ?>" class="form-control" placeholder="Cari nama/panggilan">
        </div>
        <div class="col-md-4 mb-2">
          <select name="kelas_id" class="form-control">
            <option value="">Semua Kelas</option>
            <?php foreach ($kelas as $k): ?>
              <option value="<?= $k['id'] ?>" <?= (int)($kelasAktif ?? 0) === (int)$k['id'] ? 'selected' : '' ?>>
                <?= esc($k['nama_kelas']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4 mb-2">
          <button class="btn btn-primary w-100">Filter</button>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Panggilan</th>
              <th>Gereja Asal</th>
              <th>Unity</th>
              <th>Kelas</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($murid)): ?>
              <tr>
                <td colspan="7" class="text-center text-muted">Belum ada data murid.</td>
              </tr>
            <?php else: ?>
              <?php
                $uri = trim((string) uri_string(), '/');
                $isSuperadmin = str_starts_with($uri, 'dashboard/superadmin/');
              ?>
              <?php foreach ($murid as $m): ?>
                <tr>
                  <td><?= esc(trim(($m['nama_depan'] ?? '').' '.($m['nama_belakang'] ?? ''))) ?></td>
                  <td><?= esc($m['panggilan'] ?? '-') ?></td>
                  <td><?= esc($m['gereja_asal'] ?? '-') ?></td>
                  <td><?= unityBadge($m['unity'] ?? '') ?> <?= esc($m['unity'] ?? '-') ?></td>
                  <td><?= esc($m['nama_kelas'] ?? '-') ?></td>
                  <td><?= esc($m['status'] ?? '-') ?></td>
                  <td>
                    <a class="btn btn-sm btn-warning"
                       href="<?= $isSuperadmin ? base_url('dashboard/superadmin/murid/edit/'.$m['id']) : base_url('admin/murid/edit/'.$m['id']) ?>">
                      Edit
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

@endsection
