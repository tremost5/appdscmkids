<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?= esc($judul ?? 'Rekap Presensi') ?></title>

<style>
@page {
    margin: 120px 40px 60px 40px;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    color: #000;
}

/* ================= HEADER ================= */
.header {
    position: fixed;
    top: -90px;
    left: 0;
    right: 0;
    height: 80px;
    text-align: center;
    border-bottom: 2px solid #000;
}

.header img {
    position: absolute;
    left: 0;
    top: 0;
    height: 70px;
}

.header h1 {
    margin: 0;
    font-size: 16px;
    font-weight: bold;
}

.header h2 {
    margin: 3px 0;
    font-size: 13px;
    font-weight: normal;
}

.header p {
    margin: 0;
    font-size: 10px;
}

/* ================= WATERMARK ================= */
.watermark {
    position: fixed;
    top: 40%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-30deg);
    font-size: 70px;
    color: rgba(0,0,0,0.08);
    font-weight: bold;
    z-index: -1;
}

/* ================= TABLE ================= */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

th, td {
    border: 1px solid #000;
    padding: 6px;
    font-size: 10px;
}

th {
    background: #f0f0f0;
    text-align: center;
}

/* ================= FOOTER ================= */
.footer {
    position: fixed;
    bottom: -40px;
    left: 0;
    right: 0;
    text-align: right;
    font-size: 9px;
}
</style>
</head>

<body>

<!-- WATERMARK -->
<div class="watermark">DSCM KIDS</div>

<!-- HEADER -->
<div class="header">
    <img src="<?= FCPATH ?>assets/dscmkids.png">
    <h1>DSCM KIDS</h1>
    <h2>Laporan Rekap Presensi</h2>
    <p><?= esc($subjudul ?? 'Tanggal: '.$tanggal) ?></p>
</div>

<!-- FOOTER -->
<div class="footer">
    Dicetak: <?= date('d/m/Y H:i') ?>
</div>

<!-- CONTENT -->
<table>
<thead>
<tr>
    <th>No</th>
    <th>Nama</th>
    <th>Kelas</th>
    <th>Status</th>
    <th>Lokasi</th>
    <th>Jam</th>
    <th>Guru</th>
</tr>
</thead>

<tbody>
<?php if (empty($data)): ?>
<tr>
    <td colspan="7" align="center">Tidak ada data</td>
</tr>
<?php endif; ?>

<?php $no = 1; foreach ($data as $d): ?>
<tr>
    <td align="center"><?= $no++ ?></td>
    <td><?= esc(trim($d['nama_depan'].' '.$d['nama_belakang'])) ?></td>
    <td align="center"><?= esc($d['nama_kelas'] ?? '-') ?></td>
    <td align="center"><strong>HADIR</strong></td>
    <td align="center"><?= esc($d['nama_lokasi'] ?? '-') ?></td>
    <td align="center"><?= esc($d['jam']) ?></td>
    <td><?= esc(trim(($d['guru_depan'] ?? '').' '.($d['guru_belakang'] ?? ''))) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</body>
</html>
