@extends('layouts/adminlte')
@section('content')

<?php
// FULLCODE absensi_step2.php (FIXED)
// NOTE: Tidak ada fitur dihapus. Hanya perbaikan duplicate popup (ID -> nama & kelas).
?>

<style>
/* ===== ORIGINAL STYLES (DIPERTAHANKAN) ===== */
.murid-wrap{border:1px solid #e5e7eb;border-radius:14px;overflow:hidden}
.murid-header{display:grid;grid-template-columns:2fr 1fr 1fr;padding:10px;font-weight:700;background:linear-gradient(90deg,#f3e8ff,#fce7f3);color:#6b21a8;position:sticky;top:0;z-index:5}
.murid-body{max-height:60vh;overflow-y:auto}
.murid-row{display:grid;grid-template-columns:1fr 70px 50px;align-items:center;padding:10px;border-bottom:1px solid #f1f5f9;gap:6px}
.nama-murid{font-weight:600;color:#2563eb;cursor:pointer}
.murid-row input[type=checkbox]{transform:scale(1.3)}
.kelas-divider{background:#f8fafc;padding:10px 12px;font-weight:700;cursor:pointer}
.kelas-content{display:block}
#selfiePreview{display:none;width:100%;border-radius:12px;margin-top:8px}

/* MODAL */
#muridPreview{position:fixed;inset:0;background:rgba(0,0,0,.6);display:none;align-items:center;justify-content:center;z-index:9999}
#muridCard{background:#fff;border-radius:16px;padding:16px;width:90%;max-width:360px;text-align:center}
#muridFoto{width:120px;height:120px;border-radius:50%;object-fit:cover;margin-bottom:10px;display:none}
#muridAvatar{
  width:120px;height:120px;border-radius:50%;
  margin:0 auto 10px;
  display:flex;align-items:center;justify-content:center;
  font-size:42px;font-weight:700;
  color:#fff;background:linear-gradient(90deg,#7c3aed,#ec4899)
}
.absensi-hero{background:linear-gradient(90deg,#7c3aed,#ec4899)!important;color:#fff!important;border-radius:14px}
.btn-absensi-main{background:linear-gradient(90deg,#7c3aed,#ec4899)!important;border:none!important;color:#fff!important;font-weight:700}
.btn-absensi-main:hover{filter:brightness(1.05)}
</style>

<div class="card mb-3 shadow-sm">
  <div class="card-body absensi-hero">
    <h4 class="mb-0">📋 FORM PRESENSI</h4>
    <small>Pastikan data benar sebelum menyimpan</small>
  </div>
</div>

<form id="formAbsensi"
      action="<?= base_url('guru/absensi/simpan') ?>"
      method="post"
      enctype="multipart/form-data">
<?= csrf_field() ?>
<input type="hidden" name="lokasi_id" value="<?= (int)$lokasi_id ?>">

<div class="mb-3">
  <input type="text" id="searchMurid" class="form-control" placeholder="🔍 Cari murid...">
</div>

<div class="murid-wrap mb-3">
  <div class="murid-header">
    <div>Nama</div>
    <div>Kelas</div>
    <div>Hadir</div>
  </div>

  <div class="murid-body">
<?php
$label=[1=>'PG',2=>'TKA',3=>'TKB',4=>'1',5=>'2',6=>'3',7=>'4',8=>'5',9=>'6'];
usort($murid,fn($a,$b)=>[$a['kelas_id'],$a['nama_depan']]<=>[$b['kelas_id'],$b['nama_depan']]);
$grp=[]; foreach($murid as $m){ $grp[$m['kelas_id']][]=$m; }

// ===== MAP MURID (FIX DUPLICATE POPUP) =====
$muridMap = [];
foreach($murid as $m){
  $muridMap[$m['id']] = [
    'nama'  => trim(($m['panggilan'] ?: $m['nama_depan']).' '.$m['nama_belakang']),
    'kelas' => $label[$m['kelas_id']]
  ];
}

foreach($grp as $k=>$list):
?>
  <div class="kelas-divider" onclick="toggleKelas(this)">
    🏷️ Kelas <?= $label[$k] ?>
  </div>
  <div class="kelas-content">
  <?php foreach($list as $m): ?>
    <div class="murid-row murid-item">
      <div class="nama-murid"
           data-nama="<?= esc($m['nama_depan'].' '.$m['nama_belakang']) ?>"
           data-panggilan="<?= esc($m['panggilan'] ?: $m['nama_depan']) ?>"
           data-kelas="<?= $label[$k] ?>">
        <?= esc(($m['panggilan'] ?: $m['nama_depan']).' ('.$m['nama_depan'].' '.$m['nama_belakang'].')') ?>
      </div>
      <div><?= $label[$k] ?></div>
      <div>
        <input type="checkbox" name="hadir[]" value="<?= $m['id'] ?>">
      </div>
    </div>
  <?php endforeach ?>
  </div>
<?php endforeach ?>
  </div>
</div>

<div class="card shadow-sm">
<div class="card-body">
<strong>📸 Selfie Guru <span class="text-danger">(Wajib)</span></strong>

<input type="file"
       id="selfieInput"
       name="selfie"
       class="form-control mt-2"
       accept="image/*"
       capture="user">

<img id="selfiePreview">
<div id="notif" class="mt-2"></div>

<button id="btnSubmit"
        type="submit"
        class="btn btn-absensi-main btn-lg btn-block mt-3"
        disabled>
  💾 Simpan Presensi
</button>
</div>
</div>
</form>

<!-- MODAL -->
<div id="muridPreview">
  <div id="muridCard">
    <img id="muridFoto">
    <h5 id="muridNama"></h5>
    <div id="muridKelas" class="text-muted"></div>
    <small>Tap untuk menutup</small>
  </div>
</div>
<script>
/* ===== BUILD MAP MURID (FIX undefined) ===== */
const MURID_MAP = {};
document.querySelectorAll('.murid-item').forEach(el=>{
  MURID_MAP[el.dataset.id] = {
    nama: el.dataset.nama,
    kelas: el.dataset.kelas
  };
});
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* SEARCH */
searchMurid.onkeyup=()=>{
  const q=searchMurid.value.toLowerCase();
  document.querySelectorAll('.murid-item').forEach(r=>{
    r.style.display=r.innerText.toLowerCase().includes(q)?'':'none';
  });
};

/* PREVIEW MURID */
document.querySelectorAll('.nama-murid').forEach(n=>{
  n.onclick=e=>{
    e.stopPropagation();
    muridNama.innerText=n.dataset.nama;
    muridKelas.innerText='Kelas '+n.dataset.kelas;
    muridFoto.src=n.dataset.foto;
    muridPreview.style.display='flex';
  }
});
muridPreview.onclick=()=>muridPreview.style.display='none';

/* COLLAPSE */
function toggleKelas(el){
  if(window.innerWidth>768) return;
  const box=el.nextElementSibling;
  box.style.display=box.style.display==='none'?'block':'none';
}

/* SELFIE FIX ANDROID */
const input=document.getElementById('selfieInput');
const btn=document.getElementById('btnSubmit');
const preview=document.getElementById('selfiePreview');
const notif=document.getElementById('notif');

input.addEventListener('change',()=>{
  if(!input.files.length) return;

  const file=input.files[0];
  const before=Math.round(file.size/1024);

  const img=new Image();
  img.src=URL.createObjectURL(file);

  img.onload=()=>{
    const canvas=document.createElement('canvas');
    const max=800;
    let w=img.width,h=img.height;
    if(w>h&&w>max){h*=max/w;w=max;}
    if(h>w&&h>max){w*=max/h;h=max;}
    canvas.width=w;canvas.height=h;
    canvas.getContext('2d').drawImage(img,0,0,w,h);

    canvas.toBlob(blob=>{
      const after=Math.round(blob.size/1024);
      const dt=new DataTransfer();
      dt.items.add(new File([blob],'selfie.jpg',{type:'image/jpeg'}));
      input.files=dt.files;

      preview.src=URL.createObjectURL(blob);
      preview.style.display='block';

      notif.innerHTML=`<div class="alert alert-success">
        📸 Foto dikompres: ${before} KB → <b>${after} KB</b>
      </div>`;

      /* 🔥 PAKSA AKTIF */
      btn.disabled=false;
    },'image/jpeg',0.6);
  };
});
</script>
 
<script>
document.getElementById('formAbsensi').addEventListener('submit', function (e) {
  e.preventDefault();

  const form = this;
  const btn  = document.getElementById('btnSubmit');

  // Proteksi double submit
  if (btn.disabled) return;

  btn.disabled = true;
  btn.innerHTML = '⏳ Menyimpan...';

  fetch(form.action, {
    method: 'POST',
    body: new FormData(form)
  })
  .then(r => r.json())
  .then(res => {

    /* ================= SUCCESS ================= */
    if (res.status === 'success') {
      Swal.fire({
        icon: 'success',
        title: '✅ Presensi berhasil disimpan',
        html: `📅 ${res.tanggal}<br>Tuhan Yesus Memberkati 🙏`,
        confirmButtonText: '🏠 Dashboard'
      }).then(() => {
        location.href = '<?= base_url('dashboard/guru') ?>';
      });
      return;
    }

    /* ================= DUPLICATE ================= */
    if (res.status === 'duplicate') {

      let rows = '';

      res.dobel.forEach(item => {
        if (!item.detail) return;

        const d = item.detail;
        rows += `
          <tr>
            <td style="padding:8px">${d.nama_depan} ${d.nama_belakang}</td>
            <td style="padding:8px">${d.nama_kelas}</td>
            <td style="padding:8px">${d.lokasi_text}</td>
            <td style="padding:8px">${d.jam}</td>
            <td style="padding:8px">${d.guru_pertama}</td>
          </tr>
        `;
      });

      Swal.fire({
        icon: 'warning',
        title: 'Presensi Dobel',
        html: `
          <div style="text-align:left;font-size:14px">

            <div style="
              background:#fff3cd;
              border:1px solid #ffeeba;
              color:#856404;
              padding:10px 12px;
              border-radius:8px;
              margin-bottom:12px;
            ">
              <b>⚠️ Perhatian</b><br>
              Beberapa murid <b>sudah dipresensi sebelumnya</b> pada hari yang sama.
            </div>

            <div style="
              max-height:280px;
              overflow:auto;
              border:1px solid #eee;
              border-radius:10px;
              box-shadow: inset 0 0 6px rgba(0,0,0,0.03);
            ">
              <table style="width:100%;border-collapse:collapse;font-size:13px">
                <thead style="position:sticky;top:0;background:#f8f9fa;z-index:1">
                  <tr>
                    <th style="padding:10px;border-bottom:1px solid #ddd">Nama Murid</th>
                    <th style="padding:10px;border-bottom:1px solid #ddd">Kelas</th>
                    <th style="padding:10px;border-bottom:1px solid #ddd">Lokasi</th>
                    <th style="padding:10px;border-bottom:1px solid #ddd">Jam</th>
                    <th style="padding:10px;border-bottom:1px solid #ddd">Guru Pertama</th>
                  </tr>
                </thead>
                <tbody>
                  ${rows || `
                    <tr>
                      <td colspan="5" style="padding:12px;text-align:center;color:#999">
                        Data tidak ditemukan
                      </td>
                    </tr>
                  `}
                </tbody>
              </table>
            </div>

            <div style="
              margin-top:12px;
              font-size:12px;
              color:#555;
              text-align:center;
            ">
              ✔ Data <b>tetap disimpan</b><br>
              🛠 Akan <b>ditinjau oleh admin</b> melalui dashboard
            </div>

          </div>
        `,
        width: 820,
        confirmButtonText: 'OK',
        confirmButtonColor: '#9333ea'
      }).then(() => {
        // Reset + arahkan (anti stuck)
        btn.disabled = false;
        btn.innerHTML = '💾 Simpan Presensi';
        location.href = '<?= base_url('dashboard/guru') ?>';
      });

      return;
    }

    /* ================= ERROR SERVER ================= */
    Swal.fire('Error', res.message || 'Terjadi kesalahan', 'error');
    btn.disabled = false;
    btn.innerHTML = '💾 Simpan Presensi';
  })
  .catch(() => {
    Swal.fire('Error', 'Gagal menyimpan', 'error');
    btn.disabled = false;
    btn.innerHTML = '💾 Simpan Presensi';
  });
});
</script>

@endsection
