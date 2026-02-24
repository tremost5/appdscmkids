@extends('layouts/adminlte')
@section('content')

<h1>Statistik Presensi</h1>

<table class="table table-bordered">
<thead>
<tr>
  <th>Kelas</th>
  <th>Total Murid</th>
  <th>Hadir</th>
</tr>
</thead>
<tbody>

<?php foreach ($rows as $r): ?>
<tr>
  <td><?= esc($r['kelas_id']) ?></td>
  <td><?= esc($r['total']) ?></td>
  <td><?= esc($r['hadir']) ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

@endsection
