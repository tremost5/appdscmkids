@extends('layouts/adminlte')
@section('content')

<style>
.table{font-size:14px}
@media(max-width:768px){
  .table th,.table td{padding:8px;font-size:13px}
}
.aksi-wrap{display:flex;gap:6px;flex-wrap:wrap}
.aksi-wrap .btn{white-space:nowrap}
.row-highlight{background:#dcfce7!important}
.fab{
  position:fixed;right:16px;bottom:16px;
  background:#2563eb;color:#fff;
  width:56px;height:56px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:26px;z-index:1050
}
@media(min-width:768px){.fab{display:none}}
</style>

<section class="content-header d-flex justify-content-between align-items-center">
  <h1>Data Murid</h1>
  <a href="<?= base_url('guru/murid/create') ?>" class="btn btn-primary">
    ➕ Tambah Murid
  </a>
</section>

<section class="content">

<div class="row mb-3 align-items-end">
  <div class="col-md-4 mb-2">
    <input id="searchMurid" class="form-control" placeholder="Ketik nama / panggilan murid...">
  </div>

  <div class="col-md-3 mb-2">
    <select id="filterKelas" class="form-control">
      <option value="">Semua Kelas</option>
      <?php foreach($kelas as $k): ?>
        <option value="<?= $k['id'] ?>"><?= esc($k['nama_kelas']) ?></option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-2 mb-2">
    <button class="btn btn-primary btn-block" onclick="applyFilter()">🔍 Cari</button>
  </div>
</div>

<table class="table table-bordered">
<thead>
<tr>
  <th>Nama</th>
  <th>Panggilan</th>
  <th>Gereja Asal</th>
  <th>Unity</th>
  <th>Kelas</th>
  <th>Gender</th>
  <th width="200">Aksi</th>
</tr>
</thead>
<tbody>

<?php foreach($murid as $m): ?>
<?php
  $namaLengkap = trim($m['nama_depan'].' '.$m['nama_belakang']);
  $panggilan   = $m['panggilan'] ?: $m['nama_depan'];

  $fotoFile = $m['foto'] ?? '';
  $fotoPath = FCPATH.'uploads/murid/'.$fotoFile;
  $fotoUrl  = ($fotoFile && file_exists($fotoPath))
              ? base_url('uploads/murid/'.$fotoFile)
              : '';
?>
<tr class="murid-item"
    data-nama="<?= strtolower($namaLengkap.' '.$panggilan) ?>"
    data-kelas="<?= $m['kelas_id'] ?>">

  <td>
    <span class="text-primary murid-detail"
          style="cursor:pointer;font-weight:600"
          data-nama="<?= esc($namaLengkap) ?>"
          data-panggilan="<?= esc($panggilan) ?>"
          data-hp="<?= esc($m['no_hp']) ?>"
          data-foto="<?= esc($fotoUrl) ?>"
          data-ttl="<?= esc($m['tanggal_lahir'] ?? '-') ?>"
          data-kelas="<?= esc($m['nama_kelas']) ?>"
          data-gereja="<?= esc($m['gereja_asal'] ?? '-') ?>"
          data-unity="<?= esc($m['unity'] ?? '-') ?>"
          data-alamat="<?= esc($m['alamat'] ?? '-') ?>">
      <?= esc($namaLengkap) ?>
    </span>
  </td>

  <td><span class="badge badge-info"><?= esc($panggilan) ?></span></td>
  <td><?= esc($m['gereja_asal'] ?? '-') ?></td>
  <td><?= esc($m['unity'] ?? '-') ?></td>
  <td><?= esc($m['nama_kelas']) ?></td>
  <td><?= esc($m['jenis_kelamin']) ?></td>

  <td class="text-center">
    <div class="aksi-wrap">
      <a href="<?= base_url('guru/murid/edit/'.$m['id']) ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
      <button class="btn btn-sm btn-success btn-wa"
              data-nama="<?= esc($panggilan) ?>"
              data-kelas="<?= esc($m['nama_kelas']) ?>"
              data-hp="<?= esc($m['no_hp']) ?>">📲 WA</button>
    </div>
  </td>
</tr>
<?php endforeach ?>

</tbody>
</table>
</section>

<a href="<?= base_url('guru/murid/create') ?>" class="fab">+</a>

<!-- MODAL DETAIL -->
<div id="muridModal"
     style="display:none;position:fixed;inset:0;
            background:rgba(0,0,0,.6);
            z-index:20000;
            align-items:center;justify-content:center"
     onclick="closeMuridModal()">

  <div onclick="event.stopPropagation()"
       style="background:#fff;border-radius:18px;
              padding:20px;width:90%;max-width:380px;text-align:center">

    <!-- FOTO / AVATAR -->
    <img id="muridFoto"
         style="width:120px;height:120px;
                border-radius:50%;
                object-fit:cover;
                display:none;
                margin:0 auto 10px">

    <div id="muridAvatar"
         style="width:120px;height:120px;
                border-radius:50%;
                margin:0 auto 10px;
                display:flex;
                align-items:center;
                justify-content:center;
                font-size:42px;
                font-weight:700;
                color:#fff;
                background:#2563eb"></div>

    <h5 id="muridNama"></h5>
    <div class="text-muted mb-2">(<span id="muridPanggilan"></span>)</div>

    <div class="text-muted" style="font-size:14px">
      🎂 <span id="muridTtl"></span><br>
      ⛪ <span id="muridGereja"></span><br>
      👥 <span id="muridUnity"></span><br>
      🏷️ <span id="muridKelas"></span><br>
      📍 <span id="muridAlamat"></span><br>
      📞 <span id="muridHp"></span>
    </div>

    <button id="btnWaModal" class="btn btn-success btn-sm mt-3">📲 WhatsApp</button>
    <small class="text-muted d-block mt-2">Tap di luar untuk menutup</small>
  </div>
</div>

<script>
function getMuridModal(){
  const modal = document.getElementById('muridModal');
  if (modal && modal.parentElement !== document.body) {
    document.body.appendChild(modal);
  }
  return modal;
}

function openMuridModal() {
  const modal = getMuridModal();
  if (!modal) return;
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeMuridModal() {
  const modal = document.getElementById('muridModal');
  if (!modal) return;
  modal.style.display = 'none';
  document.body.style.overflow = '';
}

function applyFilter(){
  const q=document.getElementById('searchMurid').value.toLowerCase();
  const k=document.getElementById('filterKelas').value;
  document.querySelectorAll('.murid-item').forEach(r=>{
    r.style.display=(r.dataset.nama.includes(q)&&(!k||r.dataset.kelas===k))?'':'none';
  });
}

document.querySelectorAll('.murid-detail').forEach(el=>{
  el.onclick=()=>{
    const foto=document.getElementById('muridFoto');
    const avatar=document.getElementById('muridAvatar');

    document.getElementById('muridNama').innerText=el.dataset.nama;
    document.getElementById('muridPanggilan').innerText=el.dataset.panggilan;
    document.getElementById('muridTtl').innerText=el.dataset.ttl;
    document.getElementById('muridGereja').innerText=el.dataset.gereja;
    document.getElementById('muridUnity').innerText=el.dataset.unity;
    document.getElementById('muridKelas').innerText=el.dataset.kelas;
    document.getElementById('muridAlamat').innerText=el.dataset.alamat;
    document.getElementById('muridHp').innerText=el.dataset.hp;

    if(el.dataset.foto){
      foto.src=el.dataset.foto;
      foto.style.display='block';
      avatar.style.display='none';
    }else{
      avatar.innerText=el.dataset.panggilan
        .split(' ')
        .map(x=>x[0])
        .slice(0,2)
        .join('')
        .toUpperCase();
      avatar.style.display='flex';
      foto.style.display='none';
    }

    let hp=el.dataset.hp.replace(/[^0-9]/g,'');
    if(hp.startsWith('0'))hp='62'+hp.slice(1);
    document.getElementById('btnWaModal').onclick=()=>{
      window.open(`https://wa.me/${hp}`);
    };

    openMuridModal();
  };
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('searchMurid');
  const filterKelas = document.getElementById('filterKelas');

  // 🔥 REALTIME SEARCH (KETIK LANGSUNG JALAN)
  searchInput.addEventListener('input', applyFilter);

  // 🔥 REALTIME FILTER KELAS
  filterKelas.addEventListener('change', applyFilter);
});
</script>

<?php if (session()->getFlashdata('success')): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: <?= json_encode(session()->getFlashdata('success')) ?>,
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true
    });
});
</script>
<?php endif; ?>

@endsection
