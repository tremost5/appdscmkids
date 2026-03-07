<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?= esc($judul ?? 'Rekap Presensi') ?></title>

<style>
body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    margin: 0;
    padding: 0;
}

/* WATERMARK */
.watermark {
    position: fixed;
    top: 45%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-30deg);
    font-size: 70px;
    color: #000;
    opacity: 0.06;
    z-index: -1000;
    font-weight: bold;
    letter-spacing: 6px;
}

/* HEADER */
.header {
    border-bottom: 2px solid #000;
    padding-bottom: 8px;
    margin-bottom: 12px;
}
.header-table {
    width: 100%;
}
.header-title {
    text-align: center;
}
.header-title h2 {
    margin: 0;
    font-size: 16px;
}
.header-title p {
    margin: 2px 0 0;
    font-size: 11px;
}

/* TABLE */
table.data {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
table.data th,
table.data td {
    border: 1px solid #000;
    padding: 6px;
}
table.data th {
    background: #f0f0f0;
    text-align: center;
}
</style>
</head>

<body>
<?php
$modeAktif = $mode ?? 'all';
$guruLabel = $modeAktif === 'all' ? 'PIC' : ($modeAktif === 'unity' ? 'Mentor' : 'Guru');
?>

<div class="watermark">DSCM KIDS</div>

<div class="header">
<table class="header-table">
<tr>
    <td width="15%" align="center">
    <img src="<?= base_url('assets/logo/dscmkids.png') ?>" style="width:70px;">
</td>

    <td width="85%" class="header-title">
        <h2><?= esc($judul ?? 'REKAP PRESENSI') ?></h2>
        <p>Periode: <?= esc($start ?? '-') ?> s/d <?= esc($end ?? '-') ?></p>
    </td>
</tr>
</table>
</div>

<table class="data">
<thead>
<tr>
    <th>No</th>
    <th>Tanggal</th>
    <th>Nama</th>
    <th>Kelas</th>
    <?php if ($modeAktif === 'all'): ?>
    <th>Jenis</th>
    <?php endif; ?>
    <th>Status</th>
    <th>Lokasi</th>
    <th>Jam</th>
    <th><?= esc($guruLabel) ?></th>
</tr>
</thead>
<tbody>

<?php if (empty($data)): ?>
<tr><td colspan="<?= $modeAktif === 'all' ? '9' : '8' ?>" align="center">Tidak ada data</td></tr>
<?php endif; ?>

<?php $no=1; foreach ($data as $d): ?>
<tr>
    <td align="center"><?= $no++ ?></td>
    <td align="center"><?= esc($tanggal ?? '-') ?></td>
    <td><?= esc(trim($d['nama_depan'].' '.$d['nama_belakang'])) ?></td>
    <td align="center"><?= esc($d['nama_kelas'] ?? '-') ?></td>
    <?php if ($modeAktif === 'all'): ?>
    <td align="center"><?= esc(ucfirst($d['jenis_presensi'] ?? 'reguler')) ?></td>
    <?php endif; ?>
    <td align="center">HADIR</td>
    <td align="center"><?= esc($d['nama_lokasi'] ?? '-') ?></td>
    <td align="center"><?= esc($d['jam'] ?? '-') ?></td>
    <td><?= esc(trim(($d['guru_depan'] ?? '').' '.($d['guru_belakang'] ?? ''))) ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

</body>
</html>
