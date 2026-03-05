@extends('layouts/adminlte')
@section('content')

<h1 class="mb-4">Statistik Kehadiran</h1>

<form method="get" class="mb-3">
    <div style="max-width:240px">
        <label class="small">Jenis Presensi</label>
        <select name="mode" class="form-control form-control-sm" onchange="this.form.submit()">
            <option value="all" <?= (($mode ?? 'all')==='all')?'selected':'' ?>>Semua</option>
            <option value="reguler" <?= (($mode ?? '')==='reguler')?'selected':'' ?>>Reguler</option>
            <option value="unity" <?= (($mode ?? '')==='unity')?'selected':'' ?>>Unity</option>
        </select>
    </div>
</form>

<div class="row">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5>Total Murid</h5>
                <h2><?= $total_murid ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5>Hadir Hari Ini</h5>
                <h2><?= $hadir_hari_ini ?></h2>
            </div>
        </div>
    </div>
</div>

<hr>

<h5>Grafik Kehadiran per Bulan</h5>
<canvas id="chartAbsensi"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const dataBulan = <?= json_encode($absen_bulan_ini) ?>;

const labels = dataBulan.map(d => 'Bulan ' + d.bulan);
const totals = dataBulan.map(d => d.total);

new Chart(document.getElementById('chartAbsensi'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Jumlah Hadir',
            data: totals
        }]
    }
});
</script>

@endsection
