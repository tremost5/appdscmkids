@extends('layouts/adminlte')
@section('content')

<h4>🏆 Ranking Murid Rajin</h4>

<form class="row mb-3">
  <div class="col-md-3">
    <input type="date" name="start" value="<?= esc($start) ?>" class="form-control">
  </div>

  <div class="col-md-3">
    <input type="date" name="end" value="<?= esc($end) ?>" class="form-control">
  </div>

  <div class="col-md-3">
    <select name="kelas_id" class="form-control">
      <option value="">Semua Kelas</option>
      <?php foreach ($kelas as $k): ?>
        <option value="<?= $k['id'] ?>"
          <?= $kelasId == $k['id'] ? 'selected' : '' ?>>
          <?= esc($k['nama_kelas']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-3">
    <button class="btn btn-primary w-100">🔍 Tampilkan</button>
  </div>
</form>

<a class="btn btn-danger btn-sm mb-3"
   href="<?= base_url(
        'admin/ranking-murid/export-pdf'
        . '?start='.$start
        . '&end='.$end
        . '&kelas_id='.$kelasId
   ) ?>">
   📄 Export PDF
</a>

<table class="table table-bordered table-striped">
  <thead class="thead-dark">
    <tr>
      <th width="70" class="text-center">Rank</th>
      <th>Nama Murid</th>
      <th>Kelas</th>
      <th width="140" class="text-center">Point</th>
    </tr>
  </thead>
  <tbody>

<?php if (empty($rows)): ?>
<tr>
  <td colspan="4" class="text-center text-muted">
    Belum ada data presensi
  </td>
</tr>
<?php endif; ?>

<?php
$rank = 1;
foreach ($rows as $r):
  $nama = trim(($r['nama_depan'] ?? '').' '.($r['nama_belakang'] ?? ''));
  $point = (int)$r['total_point'];
?>

<tr>
  <td class="text-center">
    <?= $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : ($rank == 3 ? '🥉' : $rank)) ?>
  </td>

  <td><?= esc($nama ?: '-') ?></td>
  <td><?= esc($r['nama_kelas'] ?? '-') ?></td>

  <td class="text-center">
    <span class="badge badge-success"><?= $point ?></span>
    <?php if ($rank <= 3 && $point > 0): ?>
      <span class="badge badge-warning ml-1">Murid Rajin</span>
    <?php endif ?>
  </td>
</tr>

<?php
$rank++;
endforeach;
?>

  </tbody>
</table>

@endsection
