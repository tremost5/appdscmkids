@extends('layouts/adminlte')
@section('content')

<style>
.admin-shell {
  max-width: 1150px;
  margin: 0 auto;
  display: grid;
  gap: 18px;
  padding: 4px 0 84px;
}
.admin-hero {
  border-radius: 18px;
  border: 1px solid #dbe4f0;
  background: linear-gradient(135deg, #0f766e 0%, #0284c7 100%);
  color: #fff;
  padding: 18px 20px;
  box-shadow: 0 16px 34px rgba(2, 132, 199, 0.25);
}
.admin-hero h3 {
  margin: 0;
  font-weight: 800;
  letter-spacing: -0.02em;
}
.admin-hero small {
  opacity: .92;
}
.kpi-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(4, minmax(0, 1fr));
}
.kpi-card {
  border: 1px solid #dbe4f0;
  border-radius: 14px;
  padding: 14px;
  background: #fff;
}
.kpi-card .label {
  color: #64748b;
  font-size: .82rem;
}
.kpi-card .value {
  margin-top: 2px;
  font-size: 1.55rem;
  font-weight: 800;
  line-height: 1.1;
  color: #0f172a;
}
.panel-grid {
  display: grid;
  gap: 16px;
  grid-template-columns: 1.55fr 1fr;
}
.guru-alert-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}
.chart-card {
  border: 1px solid #dbe4f0;
  border-radius: 16px;
  background: #fff;
  padding: 14px;
}
.chart-card h6 {
  margin: 0;
  font-weight: 800;
}
.chart-sub {
  color: #64748b;
  font-size: .82rem;
}
.chart-wrap {
  height: 260px;
  margin-top: 8px;
}
.side-stack {
  display: grid;
  gap: 16px;
}
.list-item {
  padding: 10px 0;
  border-bottom: 1px dashed #e2e8f0;
}
.list-item:last-child {
  border-bottom: 0;
  padding-bottom: 0;
}
@media (max-width: 991.98px) {
  .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .guru-alert-grid { grid-template-columns: 1fr; }
  .panel-grid { grid-template-columns: 1fr; }
}
</style>

<section class="content">
  <div class="admin-shell">
    <div class="admin-hero">
      <h3>Dashboard Admin</h3>
      <small>Kontrol aktivitas guru, presensi, dan monitoring operasional harian.</small>
    </div>

    <div id="alert-absensi-dobel" class="alert alert-danger mb-0 <?= (int) ($dobelHariIni ?? 0) > 0 ? '' : 'd-none' ?>">
      <strong>Presensi dobel terdeteksi.</strong>
      Segera cek menu Presensi Dobel untuk penanganan.
      <a href="<?= base_url('admin/absensi-dobel') ?>" class="btn btn-sm btn-light ml-2">Buka</a>
    </div>

    <div class="guru-alert-grid">
      <div id="alert-guru-nonaktif" class="alert alert-warning mb-0 <?= (int) ($guruNonaktifCount ?? 0) > 0 ? '' : 'd-none' ?>">
        <strong>Guru Nonaktif:</strong>
        <span id="guru-nonaktif-count"><?= (int) ($guruNonaktifCount ?? 0) ?></span> akun perlu ditinjau.
        <a href="<?= base_url('admin/guru') ?>" class="btn btn-sm btn-dark ml-2">Cek Data Guru</a>
      </div>

      <div id="alert-guru-baru" class="alert alert-info mb-0 <?= (int) ($guruBaruHariIniCount ?? 0) > 0 ? '' : 'd-none' ?>">
        <strong>Guru Baru Daftar Hari Ini:</strong>
        <span id="guru-baru-count"><?= (int) ($guruBaruHariIniCount ?? 0) ?></span> akun.
        <a href="<?= base_url('admin/guru') ?>" class="btn btn-sm btn-primary ml-2">Review</a>
      </div>
    </div>

    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="label">Total Guru</div>
        <div class="value"><?= (int) ($total_guru ?? 0) ?></div>
      </div>
      <div class="kpi-card">
        <div class="label">Guru Online</div>
        <div class="value"><?= (int) ($guru_online ?? 0) ?></div>
      </div>
      <div class="kpi-card">
        <div class="label">Hadir Hari Ini</div>
        <div class="value"><?= (int) ($todayHadir ?? 0) ?></div>
      </div>
      <div class="kpi-card">
        <div class="label">Avg Hadir / Hari</div>
        <div class="value"><?= esc((string) ($avgHarian ?? 0)) ?></div>
      </div>
    </div>

    <div class="panel-grid">
      <div class="chart-card">
        <h6>Grafik Kehadiran Minggu Ini</h6>
        <div class="chart-sub">Total status hadir seluruh kelas per hari (7 hari terakhir)</div>
        <div class="chart-wrap"><canvas id="chartAdminWeekly"></canvas></div>
      </div>

      <div class="side-stack">
        <div class="chart-card">
          <h6>Ringkasan Status Guru</h6>
          <div class="list-item">
            <strong>Online</strong>
            <div class="chart-sub"><?= (int) ($guru_online ?? 0) ?> guru</div>
          </div>
          <div class="list-item">
            <strong>Idle</strong>
            <div class="chart-sub"><?= (int) ($guru_idle ?? 0) ?> guru</div>
          </div>
          <div class="list-item">
            <strong>Offline</strong>
            <div class="chart-sub"><?= (int) ($guru_offline ?? 0) ?> guru</div>
          </div>
          <div class="list-item">
            <strong>Presensi Dobel Hari Ini</strong>
            <div class="chart-sub"><?= (int) ($dobelHariIni ?? 0) ?> data belum resolve</div>
          </div>
        </div>

        <div class="chart-card">
          <h6>Ulang Tahun Guru (±3 Hari)</h6>
          <?php if (empty($ultahGuru)): ?>
            <div class="chart-sub mt-2">Tidak ada ulang tahun guru dalam rentang ini.</div>
          <?php else: ?>
            <?php foreach ($ultahGuru as $g): ?>
              <div class="list-item">
                <strong><?= esc(($g['nama_depan'] ?? '').' '.($g['nama_belakang'] ?? '')) ?></strong>
                <div class="chart-sub">
                  <?= date('d M', strtotime($g['tanggal_lahir'])) ?> • <?= esc((string) ($g['usia'] ?? '-')) ?> tahun
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="panel-grid">
      <div class="chart-card">
        <h6>Grafik Kehadiran Bulan Ini</h6>
        <div class="chart-sub">Distribusi jumlah hadir dari tanggal 1 hingga hari ini</div>
        <div class="chart-wrap"><canvas id="chartAdminMonthly"></canvas></div>
      </div>

      <div class="chart-card">
        <h6>Materi Minggu Ini</h6>
        <?php if (empty($materiMingguIni)): ?>
          <div class="chart-sub mt-2">Belum ada materi minggu ini.</div>
        <?php else: ?>
          <?php foreach ($materiMingguIni as $m): ?>
            <div class="list-item">
              <strong><?= esc($m['judul']) ?></strong>
              <div class="chart-sub">
                Kelas <?= esc($m['nama_kelas'] ?? '-') ?> •
                <?= date('d M Y', strtotime($m['created_at'])) ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="chart-card">
      <h6>Pendaftar Guru Baru Hari Ini</h6>
      <?php if (empty($guruBaruHariIniList)): ?>
        <div class="chart-sub mt-2">Belum ada pendaftaran guru baru hari ini.</div>
      <?php else: ?>
        <?php foreach ($guruBaruHariIniList as $g): ?>
          <div class="list-item">
            <strong><?= esc(trim(($g['nama_depan'] ?? '') . ' ' . ($g['nama_belakang'] ?? ''))) ?></strong>
            <div class="chart-sub">
              <?= date('d M Y H:i', strtotime((string) $g['created_at'])) ?> -
              status <?= esc((string) ($g['status'] ?? '-')) ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const adminWeeklyLabels = @json($weeklyLabels ?? []);
const adminWeeklyData = @json($weeklyData ?? []);
const adminMonthlyLabels = @json($monthlyLabels ?? []);
const adminMonthlyData = @json($monthlyData ?? []);

const adminGrid = { color: 'rgba(148,163,184,.25)', drawTicks: false };

new Chart(document.getElementById('chartAdminWeekly'), {
  type: 'line',
  data: {
    labels: adminWeeklyLabels,
    datasets: [{
      data: adminWeeklyData,
      borderColor: '#0284c7',
      backgroundColor: 'rgba(2,132,199,.14)',
      borderWidth: 3,
      tension: 0.35,
      pointRadius: 4,
      pointHoverRadius: 6,
      fill: true
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false }, ticks: { color: '#64748b' } },
      y: { beginAtZero: true, ticks: { precision: 0, color: '#64748b' }, grid: adminGrid }
    }
  }
});

new Chart(document.getElementById('chartAdminMonthly'), {
  type: 'bar',
  data: {
    labels: adminMonthlyLabels,
    datasets: [{
      data: adminMonthlyData,
      borderRadius: 8,
      backgroundColor: 'rgba(15,118,110,.8)',
      borderColor: '#0f766e',
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false }, ticks: { color: '#64748b', maxTicksLimit: 16 } },
      y: { beginAtZero: true, ticks: { precision: 0, color: '#64748b' }, grid: adminGrid }
    }
  }
});

function refreshDashboardAlert() {
  const url = "<?= base_url('admin/absensi-dobel/count') ?>" + '?_t=' + Date.now();
  fetch(url, {
    cache: 'no-store',
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => {
    if (!r.ok) throw new Error('HTTP ' + r.status);
    return r.json();
  })
  .then(res => {
    const alertBox = document.getElementById('alert-absensi-dobel');
    if (!alertBox) return;
    res.total > 0 ? alertBox.classList.remove('d-none') : alertBox.classList.add('d-none');
  })
  .catch(() => {
    // Pertahankan state terakhir saat jaringan/error respons.
  });
}

function refreshGuruAlert() {
  const url = "<?= base_url('admin/guru/notif-count') ?>" + '?_t=' + Date.now();
  fetch(url, {
    cache: 'no-store',
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => {
    if (!r.ok) throw new Error('HTTP ' + r.status);
    return r.json();
  })
  .then(res => {
    const nonaktif = Number(res.nonaktif);
    const baru = Number(res.baru_hari_ini);

    if (!Number.isFinite(nonaktif) || !Number.isFinite(baru)) {
      return;
    }

    const alertNonaktif = document.getElementById('alert-guru-nonaktif');
    const alertBaru = document.getElementById('alert-guru-baru');
    const countNonaktif = document.getElementById('guru-nonaktif-count');
    const countBaru = document.getElementById('guru-baru-count');

    if (countNonaktif) countNonaktif.innerText = String(nonaktif);
    if (countBaru) countBaru.innerText = String(baru);

    if (alertNonaktif) {
      nonaktif > 0 ? alertNonaktif.classList.remove('d-none') : alertNonaktif.classList.add('d-none');
    }
    if (alertBaru) {
      baru > 0 ? alertBaru.classList.remove('d-none') : alertBaru.classList.add('d-none');
    }
  })
  .catch(() => {
    // Jangan sembunyikan card saat fetch gagal/kena cache invalid.
  });
}

refreshDashboardAlert();
refreshGuruAlert();
setInterval(refreshDashboardAlert, 10000);
setInterval(refreshGuruAlert, 30000);
</script>

@endsection
