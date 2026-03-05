@extends('layouts/adminlte')
@section('content')

<?php
$mapKelas = [
    1=>'PG',2=>'TKA',3=>'TKB',
    4=>'1',5=>'2',6=>'3',
    7=>'4',8=>'5',9=>'6'
];
?>

<h3>Rekap Per Kelas</h3>

<table class="table table-bordered">
<thead>
<tr>
  <th>Kelas</th>
  <th>Hadir</th>
  <th>Tidak Hadir</th>
</tr>
</thead>
<tbody>

<?php foreach ($rekap as $kelas=>$r): ?>
<tr>
  <td><?= esc($mapKelas[$kelas] ?? '-') ?></td>
  <td><?= $r['HADIR'] ?></td>
  <td><?= $r['TIDAK HADIR'] ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

@endsection
