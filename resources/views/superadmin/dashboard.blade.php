@extends('layouts/adminlte')
@section('content')

<style>
.super-shell {
  max-width: 1150px;
  margin: 0 auto;
  display: grid;
  gap: 18px;
  padding: 4px 0 84px;
}
.super-hero {
  border-radius: 18px;
  border: 1px solid #dbe4f0;
  background: linear-gradient(135deg, #1d4ed8 0%, #0f766e 100%);
  color: #fff;
  padding: 18px 20px;
  box-shadow: 0 16px 34px rgba(29, 78, 216, 0.24);
}
.super-hero h3 {
  margin: 0;
  font-weight: 800;
  letter-spacing: -0.02em;
}
.kpi-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(5, minmax(0, 1fr));
}
.kpi-card {
  border: 1px solid #dbe4f0;
  border-radius: 14px;
  padding: 14px;
  background: #fff;
}
.kpi-card .label {
  color: #64748b;
  font-size: .8rem;
}
.kpi-card .value {
  margin-top: 2px;
  font-size: 1.45rem;
  font-weight: 800;
  color: #0f172a;
}
.panel-grid {
  display: grid;
  gap: 16px;
  grid-template-columns: 1.6fr 1fr;
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
.list-item {
  padding: 10px 0;
  border-bottom: 1px dashed #e2e8f0;
}
.list-item:last-child {
  border-bottom: 0;
  padding-bottom: 0;
}
.emergency-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.emergency-btn {
  border-radius: 12px;
  font-weight: 700;
}
@media (max-width: 1200px) {
  .kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 991.98px) {
  .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .panel-grid { grid-template-columns: 1fr; }
}
</style>

<section class="content">
  <div class="super-shell">
    <div class="super-hero">
      <h3>Superadmin Control Center</h3>
      <small>Kontrol menyeluruh pengguna, keamanan sistem, dan kualitas data presensi.</small>
    </div>

    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="label">Total User</div>
        <div class="value"><?= (int) ($total_users ?? 0) ?></div>
      </div>
      <div class="kpi-card">
        <div class="label">User Online</div>
        <div class="value"><?= (int) ($user_online ?? 0) ?></div>
      </div>
      <div class="kpi-card">
        <div class="label">Total Murid</div>
        <div class="value"><?= (int) ($total_murid ?? 0) ?></div>
      </div>
      <div class="kpi-card">
        <div class="label">Presensi Hari Ini</div>
        <div class="value"><?= (int) ($absen_hari_ini ?? 0) ?></div>
      </div>
      <div class="kpi-card">
        <div class="label">Presensi Dobel</div>
        <div class="value"><?= (int) ($absen_dobel ?? 0) ?></div>
      </div>
    </div>

    <div class="panel-grid">
      <div class="chart-card">
        <h6>Grafik Kehadiran Minggu Ini (Global)</h6>
        <div class="chart-sub">Jumlah status hadir dari seluruh kelas per hari</div>
        <div class="chart-wrap"><canvas id="chartSuperWeekly"></canvas></div>
      </div>

      <div class="chart-card">
        <h6>Distribusi Role User</h6>
        <div class="chart-sub">Komposisi user aktif berdasarkan role</div>
        <div class="chart-wrap"><canvas id="chartRole"></canvas></div>
      </div>
    </div>

    <div class="panel-grid">
      <div class="chart-card">
        <h6>Superadmin Activity Log</h6>
        <?php if (empty($activity)): ?>
          <div class="chart-sub mt-2">Belum ada aktivitas.</div>
        <?php else: ?>
          <?php foreach ($activity as $a): ?>
            <div class="list-item">
              <strong><?= esc($a['aksi'] ?? '-') ?></strong>
              <div class="chart-sub">
                <?= esc($a['deskripsi'] ?? '-') ?> •
                <?= date('d M Y H:i', strtotime($a['created_at'])) ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="chart-card">
        <h6>Emergency Actions</h6>
        <div class="chart-sub mb-3">Gunakan hanya saat kondisi insiden/maintenance.</div>
        <div class="emergency-actions">
          <a href="<?= base_url('superadmin/wa-template') ?>" class="btn btn-success emergency-btn">
            <i class="fab fa-whatsapp mr-1"></i> WA Template & Penerima
          </a>
          <button class="btn btn-danger emergency-btn" onclick="doAction('logout-all')">
            Force Logout Semua User
          </button>
          <button id="btnMaintenanceToggle"
                  class="btn <?= !empty($maintenanceActive) ? 'btn-secondary' : 'btn-warning' ?> emergency-btn"
                  onclick="doAction('maintenance')">
            <?= !empty($maintenanceActive) ? 'Nonaktifkan Maintenance' : 'Aktifkan Maintenance' ?>
          </button>
        </div>
        <hr>
        <h6 class="mb-2">Distribusi Unity Aktif</h6>
        <?php if (empty($unitySummary)): ?>
          <div class="chart-sub">Belum ada data unity.</div>
        <?php else: ?>
          <?php foreach ($unitySummary as $u): ?>
            <div class="chart-sub mb-1"><?= unityBadge($u['unity'] ?? '') ?> <?= esc($u['unity'] ?? '-') ?>: <?= (int) ($u['total'] ?? 0) ?> murid</div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const superWeeklyLabels = @json($weeklyLabels ?? []);
const superWeeklyData = @json($weeklyData ?? []);
const roleLabels = @json($roleLabels ?? []);
const roleData = @json($roleData ?? []);
let maintenanceActive = @json(!empty($maintenanceActive));
const gridColor = { color: 'rgba(148,163,184,.25)', drawTicks: false };

new Chart(document.getElementById('chartSuperWeekly'), {
  type: 'line',
  data: {
    labels: superWeeklyLabels,
    datasets: [{
      data: superWeeklyData,
      borderColor: '#1d4ed8',
      backgroundColor: 'rgba(29,78,216,.14)',
      borderWidth: 3,
      pointRadius: 4,
      pointHoverRadius: 6,
      tension: 0.35,
      fill: true
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false }, ticks: { color: '#64748b' } },
      y: { beginAtZero: true, ticks: { precision: 0, color: '#64748b' }, grid: gridColor }
    }
  }
});

new Chart(document.getElementById('chartRole'), {
  type: 'doughnut',
  data: {
    labels: roleLabels,
    datasets: [{
      data: roleData,
      backgroundColor: ['#1d4ed8', '#0f766e', '#0284c7'],
      borderWidth: 0
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
        labels: { color: '#334155', boxWidth: 14 }
      }
    }
  }
});

const csrfToken = '<?= csrf_token() ?>';
const btnMaintenanceToggle = document.getElementById('btnMaintenanceToggle');

function syncMaintenanceButton() {
  if (!btnMaintenanceToggle) return;
  btnMaintenanceToggle.textContent = maintenanceActive
    ? 'Nonaktifkan Maintenance'
    : 'Aktifkan Maintenance';
  btnMaintenanceToggle.classList.toggle('btn-secondary', maintenanceActive);
  btnMaintenanceToggle.classList.toggle('btn-warning', !maintenanceActive);
}
syncMaintenanceButton();

function doAction(type) {
  if (!confirm('Yakin melakukan aksi ini?')) return;

  fetch("<?= base_url('dashboard/superadmin/action') ?>", {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: new URLSearchParams({
      type: type,
      _token: csrfToken
    })
  })
  .then(r => r.json())
  .then(r => {
    if (typeof r.maintenance_active === 'boolean') {
      maintenanceActive = r.maintenance_active;
      syncMaintenanceButton();
    }

    if (r.status === 'success') {
      toastr.success(r.message || 'Berhasil');
      if (type === 'maintenance') {
        return;
      }
      setTimeout(() => location.reload(), 1200);
      return;
    }

    toastr.error(r.message || 'Gagal');
  })
  .catch(() => toastr.error('Koneksi bermasalah'));
}
</script>

@endsection
