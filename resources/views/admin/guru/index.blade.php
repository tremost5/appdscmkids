@extends('layouts/adminlte')
@section('content')

<section class="content-header">
  <h1>Manajemen Guru</h1>
</section>

<style>
#guruBackdrop{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.35);
  z-index:20000;
  opacity:0;
  pointer-events:none;
  transition:.2s;
}
#guruBackdrop.show{
  opacity:1;
  pointer-events:auto;
}

#guruCard{
  position:fixed;
  top:50%;
  left:50%;
  transform:translate(-50%,-45%) scale(.95);
  z-index:20001;
  width:92%;
  max-width:420px;
  opacity:0;
  pointer-events:none;
  transition:.25s ease;
}
#guruCard.show{
  opacity:1;
  pointer-events:auto;
  transform:translate(-50%,-50%) scale(1);
}

#guruCard #gFoto{
  image-orientation: from-image;
  object-fit: cover;
  transition: transform .2s ease;
}
</style>

<section class="content">
<div class="card">
<div class="card-body table-responsive">

<?php $prefixGuru = session('role_id') == 1 ? 'dashboard/superadmin/guru' : 'admin/guru'; ?>
<a href="<?= base_url($prefixGuru . '/create') ?>"
   class="btn btn-primary mb-3">
  ➕ Tambah Guru
</a>

<table class="table table-bordered table-striped">
<thead>
<tr>
  <th>Nama</th>
  <th>Username</th>
  <th>Status</th>
  <th width="180">Aksi</th>
</tr>
</thead>
<tbody>

<?php if (empty($guru)): ?>
<tr>
  <td colspan="4" class="text-center text-muted">
    Belum ada data guru
  </td>
</tr>
<?php endif; ?>

<?php foreach ($guru as $g): ?>
<?php
  $nama  = trim($g['nama_depan'].' '.($g['nama_belakang'] ?? ''));
  $aktif = ($g['status'] === 'aktif');
?>
<tr id="row<?= $g['id'] ?>">
  <td>
    <a href="#"
       class="font-weight-bold text-primary"
       onclick="openGuru(<?= $g['id'] ?>);return false;">
      <?= esc($nama) ?>
    </a>
  </td>

  <td><?= esc($g['username']) ?></td>

  <td>
    <span id="badge<?= $g['id'] ?>"
          class="badge <?= $aktif?'badge-success':'badge-secondary' ?>">
      <?= $aktif?'Aktif':'Nonaktif' ?>
    </span>
  </td>

  <td class="text-nowrap">

    <!-- TOGGLE TANPA RELOAD -->
    <button
    class="btn btn-sm <?= $aktif?'btn-danger':'btn-success' ?>"
  onclick="toggleGuru(<?= $g['id'] ?>, this)">
      <?= $aktif?'Nonaktifkan':'Aktifkan' ?>
    </button>

    <form action="<?= base_url($prefixGuru . '/delete/'.$g['id']) ?>"
          method="post"
          style="display:inline-block"
          onsubmit="return confirm('Yakin ingin menghapus guru ini?')">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-sm btn-outline-danger">
        Hapus
      </button>
    </form>

  </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

</div>
</div>
</section>

<!-- BACKDROP -->
<div id="guruBackdrop" onclick="closeGuru()"></div>

<!-- CARD PREVIEW (READ ONLY) -->
<div class="card shadow" id="guruCard">
<div class="card-body text-center">

<img id="gFoto"
     class="img-circle mb-3"
     width="90" height="90"
     style="object-fit:cover">
<div class="mb-2">
  <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRotateFoto">
    Putar Foto
  </button>
</div>

<h5 id="gNama" class="mb-1"></h5>
<div class="text-muted mb-2" id="gUsername"></div>

<hr>

<div class="text-left">
<p><b>Alamat:</b><br><span id="gAlamat">-</span></p>
<p><b>No WhatsApp:</b><br><span id="gHp">-</span></p>
</div>

<button class="btn btn-secondary btn-block" onclick="closeGuru()">
  Tutup
</button>

</div>
</div>

<script>
let guruCsrfToken = '<?= csrf_hash() ?>';

function ensureGuruPopupElements() {
  let back = document.getElementById('guruBackdrop');
  let card = document.getElementById('guruCard');

  if (!back) {
    back = document.createElement('div');
    back.id = 'guruBackdrop';
    back.addEventListener('click', closeGuru);
    document.body.appendChild(back);
  }

  if (!card) {
    card = document.createElement('div');
    card.id = 'guruCard';
    card.className = 'card shadow';
    card.innerHTML = `
      <div class="card-body text-center">
        <img id="gFoto" class="img-circle mb-3" width="90" height="90" style="object-fit:cover">
        <div class="mb-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRotateFoto">Putar Foto</button>
        </div>
        <h5 id="gNama" class="mb-1"></h5>
        <div class="text-muted mb-2" id="gUsername"></div>
        <hr>
        <div class="text-left">
          <p><b>Alamat:</b><br><span id="gAlamat">-</span></p>
          <p><b>No WhatsApp:</b><br><span id="gHp">-</span></p>
        </div>
        <button class="btn btn-secondary btn-block" type="button" id="btnCloseGuru">Tutup</button>
      </div>
    `;
    document.body.appendChild(card);
    const btnClose = card.querySelector('#btnCloseGuru');
    if (btnClose) btnClose.addEventListener('click', closeGuru);
  }

  // Always move popup nodes to <body> so fixed positioning follows viewport
  // and is not affected by transformed/scrolled parent containers.
  if (back.parentElement !== document.body) {
    document.body.appendChild(back);
  }
  if (card.parentElement !== document.body) {
    document.body.appendChild(card);
  }

  return {
    back,
    card,
    elNama: document.getElementById('gNama'),
    elUsername: document.getElementById('gUsername'),
    elAlamat: document.getElementById('gAlamat'),
    elHp: document.getElementById('gHp'),
    elFoto: document.getElementById('gFoto'),
    btnRotateFoto: document.getElementById('btnRotateFoto')
  };
}

let manualRotation = 0;
let autoRotation = 0;
let currentGuruId = null;

function rotationStorageKey(guruId) {
  return `guru_photo_rotation_${guruId}`;
}

function loadSavedRotation(guruId) {
  try {
    const raw = localStorage.getItem(rotationStorageKey(guruId));
    const value = Number(raw);
    if (![0, 90, 180, 270].includes(value)) {
      return 0;
    }
    return value;
  } catch (e) {
    return 0;
  }
}

function saveRotation(guruId, rotation) {
  try {
    localStorage.setItem(rotationStorageKey(guruId), String(rotation));
  } catch (e) {
    // ignore storage issues (private mode/quota)
  }
}

function applyFotoRotation(elFoto) {
  if (!elFoto) return;
  const finalRotation = (autoRotation + manualRotation) % 360;
  elFoto.style.transform = `rotate(${finalRotation}deg)`;
}

/* OPEN PREVIEW */
function openGuru(id){
const popup = ensureGuruPopupElements();
currentGuruId = id;
fetch(`<?= base_url($prefixGuru . '/detail') ?>/${id}`,{
 headers:{'X-Requested-With':'XMLHttpRequest'}
})
.then(r=>r.json()).then(res=>{
 if(!res || res.status !== 'ok' || !res.data){
   alert('Detail guru tidak ditemukan.');
   return;
 }
 const g=res.data;

 popup.elNama.innerText =
   (g.nama_depan||'')+' '+(g.nama_belakang||'');

 popup.elUsername.innerText = '@'+g.username;
 popup.elAlamat.innerText   = g.alamat || '-';
 popup.elHp.innerText       = g.no_hp || '-';

 const fotoBase = '<?= rtrim(base_url('uploads/guru'), '/') ?>';
 const defaultFoto = '<?= base_url('assets/adminlte/img/avatar.png') ?>';
 popup.elFoto.src = g.foto
   ? `${fotoBase}/${g.foto}`
   : defaultFoto;
 manualRotation = loadSavedRotation(id);
 autoRotation = 0;
 applyFotoRotation(popup.elFoto);
 if (popup.btnRotateFoto) {
   popup.btnRotateFoto.onclick = function () {
     manualRotation = (manualRotation + 90) % 360;
     applyFotoRotation(popup.elFoto);
     if (currentGuruId !== null) {
       saveRotation(currentGuruId, manualRotation);
     }
   };
 }
 popup.elFoto.onload = function () {
   const w = this.naturalWidth || 0;
   const h = this.naturalHeight || 0;
   autoRotation = (w > 0 && h > 0 && (w / h) > 1.15) ? 90 : 0;
   applyFotoRotation(this);
 };
 popup.elFoto.onerror = function () {
   this.onerror = null;
   this.onload = null;
   manualRotation = 0;
   autoRotation = 0;
   applyFotoRotation(this);
   this.src = defaultFoto;
 };

 popup.back.classList.add('show');
 popup.card.classList.add('show');
 document.body.style.overflow = 'hidden';
}).catch(() => {
 alert('Gagal memuat detail guru.');
});
}

function closeGuru(){
 const back = document.getElementById('guruBackdrop');
 const card = document.getElementById('guruCard');
 if (back) back.classList.remove('show');
 if (card) card.classList.remove('show');
 document.body.style.overflow = '';
}

/* TOGGLE STATUS – TANPA RELOAD */
function toggleGuru(id, btn){
if(!confirm('Ubah status guru ini?')) return;
fetch(`<?= base_url($prefixGuru . '/toggle') ?>/${id}`,{
 method:'POST',
 headers:{
   'X-Requested-With':'XMLHttpRequest',
   'Content-Type':'application/x-www-form-urlencoded'
 },
 body:new URLSearchParams({_token:guruCsrfToken}).toString()
})
.then(async r=>{
  const res = await r.json().catch(()=>({status:'error',message:'Respons tidak valid'}));
  if(!r.ok || res.status==='error'){
    throw new Error(res.message||'Gagal mengubah status guru');
  }
  return res;
})
.then(res=>{
  const badge=document.getElementById('badge'+id);
  if(res.csrf && res.csrf.hash){
    guruCsrfToken = res.csrf.hash;
  }

  if(res.status==='nonaktif'){
    badge.className='badge badge-secondary';
    badge.innerText='Nonaktif';
    btn.className='btn btn-sm btn-success';
    btn.innerText='Aktifkan';
  }else{
    badge.className='badge badge-success';
    badge.innerText='Aktif';
    btn.className='btn btn-sm btn-danger';
    btn.innerText='Nonaktifkan';
  }
  if(window.toastr){ toastr.success(res.message||'Status guru diperbarui'); }
})
.catch(err=>{
  if(window.toastr){ toastr.error(err.message||'Gagal mengubah status guru'); }
  else { alert(err.message||'Gagal mengubah status guru'); }
});
}

</script>

@endsection
