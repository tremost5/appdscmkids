@extends('layouts/adminlte')
@section('content')

<h3 class="mb-3 text-danger">Presensi Dobel</h3>

<form method="get" class="mb-3">
  <label>Tanggal (Opsional):</label>
  <input type="date" name="tanggal" value="<?= esc($tanggal) ?>">
  <button class="btn btn-primary btn-sm">Filter</button>
  <?php if ($tanggal): ?>
    <a href="<?= base_url('admin/absensi-dobel') ?>"
       class="btn btn-secondary btn-sm">Reset</a>
  <?php endif; ?>
</form>

<?php if (empty($data)): ?>
  <div class="alert alert-success">Tidak ada konflik.</div>
<?php endif; ?>

<?php foreach ($data as $row): ?>
<div class="card mb-3 border-warning dobel-card"
     data-murid="<?= $row['murid_id'] ?>"
     data-tanggal="<?= $row['tanggal'] ?>">

  <div class="card-header bg-warning">
    <b><?= esc($row['murid_display']) ?></b> |
    Kelas <?= esc($row['kelas_nama'] ?? $row['kelas_id']) ?> |
    <?= esc($row['tanggal']) ?>
  </div>

  <div class="card-body p-0">
    <table class="table table-sm mb-0">
      <tbody>

      <?php foreach ($row['items'] as $it): ?>
      <tr class="dobel-item"
          data-detail="<?= $it['detail_id'] ?>">

        <td width="70">
          <?= esc(date('H:i', strtotime($it['created_at']))) ?>
        </td>

        <td>
          <?= esc($it['nama_lokasi'] ?? '-') ?>
        </td>

        <td>
          <?= esc($it['guru']) ?>
        </td>

        <td class="text-right">
          <button
            class="btn btn-success btn-sm btn-resolve"
            data-detail="<?= $it['detail_id'] ?>"
            data-murid="<?= $row['murid_id'] ?>"
            data-tanggal="<?= $row['tanggal'] ?>">
            HADIR
          </button>
        </td>

      </tr>
      <?php endforeach ?>

      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>

<input type="hidden" id="csrf_token" value="<?= csrf_token() ?>">

<script>
let csrfToken = document.getElementById('csrf_token').value;

document.addEventListener('click', function(e){
  const btn = e.target.closest('.btn-resolve');
  if (!btn) return;

  const card = btn.closest('.dobel-card');
  const body = new URLSearchParams({
    detail_id: btn.dataset.detail,
    murid_id: btn.dataset.murid,
    tanggal: btn.dataset.tanggal,
    _token: csrfToken
  }).toString();

  fetch('<?= base_url('admin/absensi-dobel/resolve') ?>', {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: body
  })
  .then(async (r) => {
    let payload = null;
    const contentType = (r.headers.get('content-type') || '').toLowerCase();
    if (contentType.includes('application/json')) {
      payload = await r.json();
    }

    if (!r.ok) {
      throw new Error(payload?.message || 'CSRF token mismatch atau request ditolak.');
    }

    return payload;
  })
  .then((res) => {
    if (!res || res.status !== 'ok') {
      alert((res && res.message) ? res.message : 'Gagal resolve presensi');
      return;
    }

    if (res.csrf && typeof res.csrf.hash === 'string' && res.csrf.hash.trim() !== '') {
      csrfToken = res.csrf.hash;
      document.getElementById('csrf_token').value = csrfToken;
    }

    card.style.transition = 'all .3s ease';
    card.style.opacity = 0;
    setTimeout(() => card.remove(), 300);
  })
  .catch((err) => {
    alert(err.message || 'Gagal resolve presensi');
  });
});
</script>

@endsection
