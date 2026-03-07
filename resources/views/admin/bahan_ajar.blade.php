@extends('layouts/adminlte')
@section('content')

<?php
$kelasMap=[1=>'PG',2=>'TKA',3=>'TKB',4=>'1',5=>'2',6=>'3',7=>'4',8=>'5',9=>'6'];
?>

<style>
.dropzone{
  border:2px dashed #22c55e;
  padding:20px;
  text-align:center;
  border-radius:10px;
  cursor:pointer;
  transition:.2s
}
.dropzone.drag{background:#dcfce7}
.file-info{font-size:13px;color:#555}
.progress{height:6px}
@media(max-width:768px){.aksi{display:flex;gap:6px}}
iframe,video{border:none;border-radius:8px}
</style>

<h4 class="mb-3">📚 Manajemen Bahan Ajar</h4>

<input id="search" class="form-control mb-3" placeholder="🔍 Cari materi...">

<!-- ================= UPLOAD ================= -->
<div class="card mb-3">
<div class="card-body">

<form id="formUpload" enctype="multipart/form-data">
<?= csrf_field() ?>

<input name="judul" class="form-control mb-2" placeholder="Judul materi" required>
<textarea name="catatan" class="form-control mb-2" placeholder="Catatan (opsional)"></textarea>

<select name="kelas_id" class="form-control mb-2" required>
<?php foreach($kelasMap as $i=>$k): ?>
<option value="<?= $i ?>"><?= $k ?></option>
<?php endforeach ?>
</select>

<select name="kategori" id="kategori" class="form-control mb-2">
<option value="pdf">PDF</option>
<option value="video">Video</option>
<option value="link">Link</option>
</select>

<div id="dz" class="dropzone mb-2">📎 Drag & Drop / Klik File</div>
<input type="file" name="file" id="fileInput" hidden>

<small id="fileName" class="file-info d-block mb-2">
Belum ada file dipilih
</small>

<input name="link" id="linkInput" class="form-control mb-2 d-none" placeholder="https://...">

<div id="progressWrap" class="progress d-none mb-2">
  <div id="progressBar" class="progress-bar bg-success" style="width:0%"></div>
</div>

<button class="btn btn-success btn-block">
⬆️ Upload Materi
</button>

</form>
</div>
</div>

<!-- ================= LIST ================= -->
<div class="card">
<div class="card-body p-0">
<table class="table mb-0">
<tbody id="list"></tbody>
</table>
<div class="p-2 text-center text-muted" id="pager"></div>
</div>
</div>

<!-- ================= PREVIEW ================= -->
<div class="modal fade" id="modalPreview">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
<div class="modal-header">
<h5 id="pvTitle"></h5>
<button class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body" id="pvBody"></div>
</div>
</div>
</div>

<!-- ================= EDIT ================= -->
<div class="modal fade" id="modalEdit">
<div class="modal-dialog">
<div class="modal-content">
<form id="formEdit">
<?= csrf_field() ?>
<input type="hidden" name="id" id="e_id">

<div class="modal-header">
<h5>Edit Materi</h5>
<button class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
<input name="judul" id="e_judul" class="form-control mb-2" required>
<textarea name="catatan" id="e_catatan" class="form-control mb-2"></textarea>

<select name="kelas_id" id="e_kelas" class="form-control mb-2">
<?php foreach($kelasMap as $i=>$k): ?>
<option value="<?= $i ?>"><?= $k ?></option>
<?php endforeach ?>
</select>

<select name="kategori" id="e_kategori" class="form-control mb-2">
<option value="pdf">PDF</option>
<option value="video">Video</option>
<option value="link">Link</option>
</select>

<input type="file" name="file" class="form-control mb-2">
<input name="link" id="e_link" class="form-control mb-2">
</div>

<div class="modal-footer">
<button class="btn btn-primary">💾 Simpan</button>
</div>
</form>
</div>
</div>
</div>

<script>
let page=1,q='';
const list=document.getElementById('list');
const pager=document.getElementById('pager');
const dz=document.getElementById('dz');
const fileInput=document.getElementById('fileInput');
const fileName=document.getElementById('fileName');
const kategori=document.getElementById('kategori');
const linkInput=document.getElementById('linkInput');
const progressWrap=document.getElementById('progressWrap');
const progressBar=document.getElementById('progressBar');

/* ===== DRAG & DROP ===== */
dz.onclick=()=>fileInput.click();
fileInput.onchange=()=>showFile();
dz.ondragover=e=>{e.preventDefault();dz.classList.add('drag')}
dz.ondragleave=()=>dz.classList.remove('drag')
dz.ondrop=e=>{
  e.preventDefault();dz.classList.remove('drag')
  fileInput.files=e.dataTransfer.files
  showFile()
}
function showFile(){
  if(fileInput.files.length){
    fileName.innerText='📄 '+fileInput.files[0].name
  }
}

/* ===== KATEGORI ===== */
kategori.onchange=()=>{
  if(kategori.value==='link'){
    linkInput.classList.remove('d-none')
    dz.classList.add('d-none')
    fileName.classList.add('d-none')
  }else{
    linkInput.classList.add('d-none')
    dz.classList.remove('d-none')
    fileName.classList.remove('d-none')
  }
}

/* ===== LOAD LIST ===== */
function load(){
fetch(`<?= base_url('admin/bahan-ajar/fetch') ?>?page=${page}&q=${encodeURIComponent(q)}`,{
headers:{'X-Requested-With':'XMLHttpRequest'}
})
.then(r=>r.json())
.then(res=>{
list.innerHTML='';
res.data.forEach(m=>{
list.innerHTML+=`
<tr id="r${m.id}">
<td>
<b>${m.judul}</b><br>
<small>${m.catatan||''}</small><br>
${m.file?`<small class="text-muted">📄 ${m.file}</small>`:''}
</td>
<td>${m.nama_kelas||'-'}</td>
<td class="aksi">
<button class="btn btn-info btn-sm" onclick='preview(${JSON.stringify(m)})'>👁️</button>
<button class="btn btn-warning btn-sm" onclick='openEdit(${JSON.stringify(m)})'>✏️</button>
<button class="btn btn-danger btn-sm" onclick='del(${m.id})'>🗑️</button>
</td>
</tr>`;
});
pager.innerHTML=`Page ${res.page} / ${res.last}`;
});
}
load();
search.onkeyup=e=>{q=e.target.value;page=1;load()}

/* ===== PREVIEW ===== */
function preview(d){
let html='';
if(d.kategori==='pdf') html=`<iframe src="/uploads/materi/${d.file}" style="width:100%;height:70vh"></iframe>`;
if(d.kategori==='video') html=`<video controls style="width:100%"><source src="/uploads/materi/${d.file}"></video>`;
if(d.kategori==='link') html=`<a href="${d.link}" target="_blank" class="btn btn-success">Buka Link</a>`;
pvTitle.innerText=d.judul;
pvBody.innerHTML=html;
$('#modalPreview').modal('show');
}

/* ===== EDIT ===== */
function openEdit(d){
e_id.value=d.id;
e_judul.value=d.judul;
e_catatan.value=d.catatan;
e_kelas.value=d.kelas_id;
e_kategori.value=d.kategori;
e_link.value=d.link;
$('#modalEdit').modal('show');
}

formEdit.onsubmit=e=>{
e.preventDefault();
fetch(`<?= base_url('admin/bahan-ajar/update-ajax') ?>/${e_id.value}`,{
method:'POST',
headers:{'X-Requested-With':'XMLHttpRequest'},
body:new FormData(formEdit)
}).then(async r=>{
const res = await r.json().catch(()=>({status:'error',message:'Respons tidak valid'}));
if(!r.ok || res.status!=='ok'){
  throw new Error(res.message||'Gagal memperbarui materi');
}
toastr.success('Materi diperbarui');
$('#modalEdit').modal('hide');
load();
}).catch(err=>{
toastr.error(err.message||'Gagal memperbarui materi');
});
};

/* ===== DELETE ===== */
function del(id){
if(!confirm('Hapus materi ini?'))return;
fetch(`<?= base_url('admin/bahan-ajar/delete-ajax') ?>/${id}`,{
method:'POST',
headers:{'X-Requested-With':'XMLHttpRequest'}
}).then(async r=>{
const res = await r.json().catch(()=>({status:'error',message:'Respons tidak valid'}));
if(!r.ok || res.status!=='ok'){
  throw new Error(res.message||'Gagal menghapus materi');
}
toastr.success('Dihapus');
document.getElementById('r'+id).remove();
}).catch(err=>{
toastr.error(err.message||'Gagal menghapus materi');
});
}

/* ===== UPLOAD (REALTIME PROGRESS) ===== */
formUpload.onsubmit=e=>{
e.preventDefault();

const xhr=new XMLHttpRequest();
xhr.open('POST','<?= base_url('admin/bahan-ajar/upload') ?>',true);
xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');

progressWrap.classList.remove('d-none');
progressBar.style.width='0%';

xhr.upload.onprogress=e=>{
if(e.lengthComputable){
const percent=Math.round((e.loaded/e.total)*100);
progressBar.style.width=percent+'%';
}
};

xhr.onload=()=>{
progressWrap.classList.add('d-none');
progressBar.style.width='0%';
let res={status:'ok'};
try{res=JSON.parse(xhr.responseText||'{}')}catch(e){}
if(xhr.status<200||xhr.status>=300||res.status!=='ok'){
  toastr.error(res.message||'Upload gagal');
  return;
}
toastr.success('Upload berhasil');
formUpload.reset();
fileName.innerText='Belum ada file dipilih';
linkInput.classList.add('d-none');
dz.classList.remove('d-none');
load();
};

xhr.onerror=()=>{
progressWrap.classList.add('d-none');
progressBar.style.width='0%';
toastr.error('Upload gagal');
};

xhr.send(new FormData(formUpload));
};
</script>

@endsection
