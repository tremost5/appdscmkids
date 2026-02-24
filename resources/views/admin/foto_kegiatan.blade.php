@extends('layouts/adminlte')
@section('content')

<style>
/* ===== GRID FOTO ===== */
.photo-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}
@media (min-width: 768px) {
  .photo-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}
.photo-card {
  background: #111827;
  border-radius: 10px;
  overflow: hidden;
  color: #e5e7eb;
}
.photo-card img {
  width: 100%;
  height: 180px;
  object-fit: cover;
  cursor: pointer;
}
.photo-meta {
  padding: 8px 10px;
  font-size: 12px;
}
.badge-kelas {
  background:#2563eb;
}
</style>

<section class="content-header">
  <div class="container-fluid">
    <h1>Foto Kegiatan</h1>
    <p class="text-muted">Dokumentasi selfie presensi per kelas</p>
  </div>
</section>

<section class="content">
<div class="container-fluid">

<!-- FILTER -->
<form method="get" class="row mb-3">
  <div class="col-6 col-md-3 mb-2">
    <input type="date" name="tanggal" value="<?= esc($tanggal) ?>" class="form-control">
  </div>

  <div class="col-6 col-md-3 mb-2">
    <select name="kelas" class="form-control">
      <option value="">Semua Kelas</option>
      <?php foreach ($kelasList as $k): ?>
        <option value="<?= $k['id'] ?>" <?= ($kelas==$k['id'])?'selected':'' ?>>
          <?= esc($k['kode_kelas']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-12 col-md-2 mb-2">
    <button class="btn btn-primary btn-block">🔍 Tampilkan</button>
  </div>
</form>

<?php if (empty($rows)): ?>
  <div class="text-center text-muted py-5">
    Tidak ada foto kegiatan.
  </div>
<?php else: ?>

<div class="photo-grid">
<?php foreach ($rows as $i => $r): 
  $imgUrl = base_url('uploads/selfie/'.$r['selfie_foto']);
?>
  <div class="photo-card">
    <img src="<?= $imgUrl ?>"
         data-index="<?= $i ?>"
         data-src="<?= $imgUrl ?>"
         alt="selfie">

    <div class="photo-meta">
      <div class="mb-1">
        <span class="badge badge-kelas"><?= esc($r['kode_kelas']) ?></span>
        <span class="ml-1"><?= esc($r['nama_lokasi'] ?? '-') ?></span>
      </div>
      <div>
        👨‍🏫 <?= esc(trim(($r['nama_depan'] ?? '').' '.($r['nama_belakang'] ?? ''))) ?: '-' ?>
      </div>
      <div>⏰ <?= esc($r['jam']) ?></div>
      <a href="<?= $imgUrl ?>" download class="btn btn-sm btn-light mt-2">
        ⬇️ Download
      </a>
    </div>
  </div>
<?php endforeach; ?>
</div>

<?php endif; ?>

</div>
</section>

<!-- MODAL FULLSCREEN -->
<div class="modal fade" id="photoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen">
    <div class="modal-content bg-dark">
      <div class="modal-body d-flex align-items-center justify-content-center">
        <img id="modalImg" src="" style="max-width:100%;max-height:100%">
      </div>
    </div>
  </div>
</div>

<script>
const imgs = document.querySelectorAll('.photo-card img');
let current = 0;

imgs.forEach(img => {
  img.addEventListener('click', () => {
    current = parseInt(img.dataset.index);
    openModal();
  });
});

function openModal() {
  document.getElementById('modalImg').src = imgs[current].dataset.src;
  $('#photoModal').modal('show');
}

// SWIPE
let startX = 0;
document.getElementById('photoModal').addEventListener('touchstart', e=>{
  startX = e.touches[0].clientX;
});
document.getElementById('photoModal').addEventListener('touchend', e=>{
  let diff = e.changedTouches[0].clientX - startX;
  if (diff > 50 && current > 0) {
    current--; openModal();
  }
  if (diff < -50 && current < imgs.length-1) {
    current++; openModal();
  }
});
</script>

@endsection
