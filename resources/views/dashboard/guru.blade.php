@extends('layouts/adminlte')
@section('content')

<style>
.guru-shell {
  max-width: 1100px;
  margin: 0 auto;
  display: grid;
  gap: 18px;
  padding: 4px 0 84px;
}
.guru-hero {
  border-radius: 18px;
  border: 1px solid #dbe4f0;
  background: linear-gradient(135deg, #0f766e 0%, #0ea5e9 100%);
  color: #fff;
  padding: 18px 20px;
  box-shadow: 0 16px 34px rgba(15, 118, 110, 0.28);
}
.guru-hero h3 {
  margin: 0;
  font-weight: 800;
  letter-spacing: -0.02em;
}
.guru-hero small {
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
  position: relative;
  overflow: hidden;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
}
.kpi-card::after {
  content: "";
  position: absolute;
  width: 78px;
  height: 78px;
  right: -24px;
  top: -24px;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.15);
}
.kpi-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.kpi-icon {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: .9rem;
}
.kpi-card--today .kpi-icon { color: #0f766e; background: rgba(15, 118, 110, .14); }
.kpi-card--week .kpi-icon { color: #1d4ed8; background: rgba(29, 78, 216, .14); }
.kpi-card--month .kpi-icon { color: #b45309; background: rgba(180, 83, 9, .14); }
.kpi-card--avg .kpi-icon { color: #7c3aed; background: rgba(124, 58, 237, .14); }
.kpi-card--today { border-top: 3px solid #14b8a6; }
.kpi-card--week { border-top: 3px solid #3b82f6; }
.kpi-card--month { border-top: 3px solid #f59e0b; }
.kpi-card--avg { border-top: 3px solid #8b5cf6; }
.kpi-card .label {
  color: #64748b;
  font-size: .82rem;
}
.kpi-card .value {
  margin-top: 2px;
  font-size: 1.6rem;
  font-weight: 800;
  line-height: 1.1;
  color: #0f172a;
}
.panel-grid {
  display: grid;
  gap: 16px;
  grid-template-columns: 1.55fr 1fr;
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
.card-head {
  display: flex;
  align-items: center;
  gap: 8px;
}
.head-icon {
  width: 28px;
  height: 28px;
  border-radius: 9px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: .82rem;
}
.head-icon--week { color: #0f766e; background: rgba(15, 118, 110, .14); }
.head-icon--birthday { color: #be185d; background: rgba(190, 24, 93, .14); }
.head-icon--materi { color: #1d4ed8; background: rgba(29, 78, 216, .14); }
.head-icon--month { color: #0e7490; background: rgba(14, 116, 144, .14); }
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
.row-icon {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-right: 10px;
  flex: 0 0 28px;
  font-size: .76rem;
}
.row-icon--birthday { color: #be185d; background: rgba(190, 24, 93, .12); }
.row-icon--materi { color: #1d4ed8; background: rgba(29, 78, 216, .12); }
.birthday-name-link {
  border: 0;
  background: transparent;
  padding: 0;
  font-weight: 700;
  color: #0f172a;
  cursor: pointer;
  text-align: left;
}
.birthday-name-link:hover {
  color: #be185d;
  text-decoration: underline;
}
.birthday-photo {
  width: 88px;
  height: 88px;
  border-radius: 12px;
  object-fit: cover;
  border: 1px solid #dbe4f0;
  background: #f8fafc;
}
.materi-link {
  color: #0f172a;
  font-weight: 700;
  cursor: pointer;
}
.materi-link:hover {
  text-decoration: underline;
}
.materi-preview {
  width: 100%;
  border: 1px solid #dbe4f0;
  border-radius: 12px;
  overflow: hidden;
  background: #f8fafc;
  min-height: 180px;
  display: none;
  align-items: center;
  justify-content: center;
}
.materi-preview img {
  max-width: 100%;
  max-height: 52vh;
  object-fit: contain;
  display: block;
}
.materi-preview iframe {
  width: 100%;
  height: 52vh;
  border: 0;
  display: block;
  background: #fff;
}
.materi-preview-fallback {
  color: #64748b;
  font-size: 0.9rem;
  padding: 14px;
}
@media (max-width: 991.98px) {
  .kpi-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .panel-grid {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 575.98px) {
  .kpi-icon,
  .row-icon {
    width: 26px;
    height: 26px;
    flex-basis: 26px;
    margin-right: 8px;
    border-radius: 7px;
  }
}
</style>

<section class="content">
  <div class="guru-shell">

    <div class="guru-hero">
      <h3>Dashboard Guru</h3>
      <small>
        Halo <?= esc(trim(($guru['nama_depan'] ?? '') . ' ' . ($guru['nama_belakang'] ?? ''))) ?>.
        Login terakhir:
        <?= !empty($guru['last_login']) ? date('d M Y H:i', strtotime($guru['last_login'])) : '-' ?>
      </small>
    </div>

    <div class="kpi-grid">
      <div class="kpi-card kpi-card--today">
        <div class="kpi-top">
          <div class="label">Hadir Hari Ini</div>
          <span class="kpi-icon"><i class="fas fa-user-check"></i></span>
        </div>
        <div class="value"><?= (int) ($todayCount ?? 0) ?></div>
      </div>
      <div class="kpi-card kpi-card--week">
        <div class="kpi-top">
          <div class="label">Total Minggu Ini</div>
          <span class="kpi-icon"><i class="fas fa-calendar-week"></i></span>
        </div>
        <div class="value"><?= (int) ($weeklyTotal ?? 0) ?></div>
      </div>
      <div class="kpi-card kpi-card--month">
        <div class="kpi-top">
          <div class="label">Total Bulan Ini</div>
          <span class="kpi-icon"><i class="fas fa-calendar-alt"></i></span>
        </div>
        <div class="value"><?= (int) ($monthlyTotal ?? 0) ?></div>
      </div>
      <div class="kpi-card kpi-card--avg">
        <div class="kpi-top">
          <div class="label">Rata-rata / Hari</div>
          <span class="kpi-icon"><i class="fas fa-chart-line"></i></span>
        </div>
        <div class="value"><?= esc((string) ($avgWeekly ?? 0)) ?></div>
      </div>
    </div>

    <div class="panel-grid">
      <div class="chart-card">
        <h6 class="card-head"><span class="head-icon head-icon--week"><i class="fas fa-chart-line"></i></span>Grafik Kehadiran Minggu Ini</h6>
        <div class="chart-sub">Total murid status hadir per hari (7 hari terakhir)</div>
        <div class="chart-wrap"><canvas id="chartWeekly"></canvas></div>
      </div>

      <div class="side-stack">
        <div class="chart-card chart-card--birthday">
          <h6 class="card-head"><span class="head-icon head-icon--birthday"><i class="fas fa-birthday-cake"></i></span>Ulang Tahun Murid (H-3 s/d H+3)</h6>
          <?php if (empty($ultah)): ?>
            <div class="chart-sub mt-2">Tidak ada ulang tahun terdekat.</div>
          <?php else: ?>
            <?php foreach ($ultah as $u): ?>
              <?php
                $namaMurid = trim(($u['nama_depan'] ?? '').' '.($u['nama_belakang'] ?? ''));
                $tanggalLahirRaw = (string) ($u['tanggal_lahir'] ?? '');
                $tanggalLahirFmt = $tanggalLahirRaw !== '' ? date('d M Y', strtotime($tanggalLahirRaw)) : '-';
                $ultahKe = $tanggalLahirRaw !== '' ? ((int) date('Y') - (int) date('Y', strtotime($tanggalLahirRaw))) : null;
                $fotoMurid = trim((string) ($u['foto'] ?? ''));
                $fotoMuridUrl = $fotoMurid !== ''
                  ? base_url('uploads/murid/'.rawurlencode($fotoMurid))
                  : base_url('uploads/murid/default_murid.png');
              ?>
              <div class="list-item">
                <div class="d-flex align-items-start">
                  <span class="row-icon row-icon--birthday"><i class="fas fa-gift"></i></span>
                  <div>
                    <button
                      type="button"
                      class="birthday-name-link"
                      data-role="birthday-detail"
                      data-nama="<?= esc($namaMurid, 'attr') ?>"
                      data-ultah-ke="<?= $ultahKe !== null ? (int) $ultahKe : '' ?>"
                      data-tanggal-lahir="<?= esc($tanggalLahirFmt, 'attr') ?>"
                      data-foto="<?= esc($fotoMuridUrl, 'attr') ?>"
                    >
                      <?= esc($namaMurid) ?> <?= unityBadge($u['unity'] ?? '') ?>
                    </button>
                    <div class="chart-sub">Kelas <?= esc($u['nama_kelas'] ?? '-') ?></div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="chart-card chart-card--materi">
          <h6 class="card-head"><span class="head-icon head-icon--materi"><i class="fas fa-book-open"></i></span>Materi Terbaru</h6>
          <?php if (empty($materi)): ?>
            <div class="chart-sub mt-2">Belum ada materi.</div>
          <?php else: ?>
            <?php foreach ($materi as $m): ?>
              <div class="list-item">
                <div class="d-flex align-items-start">
                  <span class="row-icon row-icon--materi"><i class="fas fa-file-alt"></i></span>
                  <div>
                    <span class="materi-link" data-id="<?= (int) $m['id'] ?>">
                      <?= esc($m['judul']) ?>
                    </span>
                    <div class="chart-sub">
                      Kelas <?= esc($m['nama_kelas'] ?? '-') ?> &bull;
                      <?= date('d M Y', strtotime($m['created_at'])) ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="chart-card">
          <h6 class="card-head"><span class="head-icon head-icon--week"><i class="fas fa-star"></i></span>Distribusi Unity Aktif</h6>
          <?php if (empty($unitySummary)): ?>
            <div class="chart-sub mt-2">Belum ada data unity.</div>
          <?php else: ?>
            <?php foreach ($unitySummary as $u): ?>
              <div class="list-item">
                <div class="chart-sub"><?= unityBadge($u['unity'] ?? '') ?> <?= esc($u['unity'] ?? '-') ?>: <?= (int) ($u['total'] ?? 0) ?> murid</div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="chart-card">
      <h6 class="card-head"><span class="head-icon head-icon--month"><i class="fas fa-chart-bar"></i></span>Grafik Kehadiran Bulan Ini</h6>
      <div class="chart-sub">Distribusi jumlah hadir harian dari tanggal 1 sampai hari ini</div>
      <div class="chart-wrap"><canvas id="chartMonthly"></canvas></div>
    </div>

  </div>
</section>

<div class="modal fade" id="materiModal">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="mJudul"></h5>
        <button class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p id="mCatatan" class="text-muted mb-0"></p>
        <div id="materiPreview" class="materi-preview mt-3"></div>
      </div>
      <div class="modal-footer">
        <a id="btnDownload" class="btn btn-success btn-block" download>Download Materi</a>
        <button class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="birthdayDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Ulang Tahun</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-start">
          <img id="birthdayPhoto" class="birthday-photo mr-3" src="<?= esc(base_url('uploads/murid/default_murid.png')) ?>" alt="Foto ulang tahun">
          <div>
            <div class="mb-2"><strong id="birthdayName">-</strong></div>
            <div class="chart-sub">Ulang Tahun ke-<span id="birthdayAge">-</span></div>
            <div class="chart-sub">Tanggal Lahir: <span id="birthdayDate">-</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const weeklyLabels = @json($weeklyLabels ?? []);
  const weeklyData = @json($weeklyData ?? []);
  const monthlyLabels = @json($monthlyLabels ?? []);
  const monthlyData = @json($monthlyData ?? []);

  const commonGrid = {
    color: 'rgba(148,163,184,.25)',
    drawTicks: false
  };

  new Chart(document.getElementById('chartWeekly'), {
    type: 'line',
    data: {
      labels: weeklyLabels,
      datasets: [{
        label: 'Hadir',
        data: weeklyData,
        tension: 0.35,
        borderWidth: 3,
        borderColor: '#0f766e',
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: '#0f766e',
        fill: true,
        backgroundColor: 'rgba(15,118,110,.14)'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#64748b' } },
        y: { beginAtZero: true, ticks: { precision: 0, color: '#64748b' }, grid: commonGrid }
      }
    }
  });

  new Chart(document.getElementById('chartMonthly'), {
    type: 'bar',
    data: {
      labels: monthlyLabels,
      datasets: [{
        label: 'Hadir',
        data: monthlyData,
        borderRadius: 8,
        backgroundColor: 'rgba(14,165,233,.78)',
        borderColor: '#0284c7',
        borderWidth: 1.2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { color: '#64748b', maxRotation: 0, autoSkip: true, maxTicksLimit: 16 }, grid: { display: false } },
        y: { beginAtZero: true, ticks: { precision: 0, color: '#64748b' }, grid: commonGrid }
      }
    }
  });

  const $materiModal = window.jQuery ? window.jQuery('#materiModal') : null;
  if ($materiModal && $materiModal.length) {
    $materiModal.appendTo('body');

    $materiModal.on('show.bs.modal', () => {
      document.querySelectorAll('.sidebar-overlay').forEach((el) => el.remove());
    });

    $materiModal.on('hidden.bs.modal', () => {
      document.body.classList.remove('modal-open');
      document.body.style.removeProperty('padding-right');
      document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove());
    });
  }

  const $birthdayModal = window.jQuery ? window.jQuery('#birthdayDetailModal') : null;
  if ($birthdayModal && $birthdayModal.length) {
    $birthdayModal.appendTo('body');
  }

  document.querySelectorAll('[data-role="birthday-detail"]').forEach((el) => {
    el.addEventListener('click', () => {
      const nama = el.getAttribute('data-nama') || '-';
      const ultahKe = el.getAttribute('data-ultah-ke') || '-';
      const tanggalLahir = el.getAttribute('data-tanggal-lahir') || '-';
      const foto = el.getAttribute('data-foto') || '<?= esc(base_url('uploads/murid/default_murid.png'), 'js') ?>';

      const nameEl = document.getElementById('birthdayName');
      const ageEl = document.getElementById('birthdayAge');
      const dateEl = document.getElementById('birthdayDate');
      const photoEl = document.getElementById('birthdayPhoto');

      if (nameEl) nameEl.textContent = nama;
      if (ageEl) ageEl.textContent = ultahKe;
      if (dateEl) dateEl.textContent = tanggalLahir;
      if (photoEl) {
        photoEl.src = foto;
        photoEl.onerror = function() {
          this.onerror = null;
          this.src = '<?= esc(base_url('uploads/murid/default_murid.png'), 'js') ?>';
        };
      }

      if ($birthdayModal && $birthdayModal.length) {
        $birthdayModal.modal('show');
      }
    });
  });

  document.querySelectorAll('.materi-link').forEach((el) => {
    el.addEventListener('click', () => {
      const id = el.dataset.id;
      fetch(`<?= base_url('guru/materi/ajax') ?>/${id}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then((res) => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then((res) => {
        if (res.error) {
          alert(res.message || 'Gagal memuat materi');
          return;
        }

        const d = res.data;
        document.getElementById('mJudul').innerText = d.judul;
        document.getElementById('mCatatan').innerText = d.catatan || '-';

        const btn = document.getElementById('btnDownload');
        const preview = document.getElementById('materiPreview');
        const ext = (d.file_ext || '').toLowerCase();
        const url = d.file_url || '';

        preview.innerHTML = '';
        preview.style.display = 'none';

        const imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        const isImage = imageExt.includes(ext);
        const isPdf = ext === 'pdf';

        if (d.file) {
          btn.href = `<?= base_url('guru/materi/download') ?>/${d.id}`;
          btn.style.display = 'block';

          if (url && isImage) {
            preview.innerHTML = `<img src=\"${url}\" alt=\"Preview materi\">`;
            preview.style.display = 'flex';
          } else if (url && isPdf) {
            preview.innerHTML = `<iframe src=\"${url}\" title=\"Preview PDF materi\"></iframe>`;
            preview.style.display = 'block';
          } else {
            preview.innerHTML = '<div class="materi-preview-fallback">Preview tidak tersedia untuk tipe file ini. Silakan gunakan tombol download.</div>';
            preview.style.display = 'flex';
          }
        } else {
          btn.style.display = 'none';
          preview.innerHTML = '<div class="materi-preview-fallback">Materi ini tidak memiliki lampiran file.</div>';
          preview.style.display = 'flex';
        }

        if ($materiModal && $materiModal.length) {
          $materiModal.modal({
            backdrop: true,
            keyboard: true,
            focus: true,
            show: true
          });
        }
      })
      .catch(() => {
        alert('Gagal memuat materi');
      });
    });
  });
});
</script>

@endsection

