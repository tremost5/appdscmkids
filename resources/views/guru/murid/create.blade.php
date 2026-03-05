@extends('layouts/adminlte')
@section('content')

<section class="content-header">
  <h1>Tambah Murid</h1>
</section>

<section class="content">
<div class="card shadow-sm">
<div class="card-body">

<form method="post"
      enctype="multipart/form-data"
      action="<?= base_url('guru/murid/store') ?>">

<?= csrf_field() ?>

<!-- NAMA DEPAN -->
<input class="form-control mb-2"
       name="nama_depan"
       placeholder="Nama Depan"
       required
       value="<?= esc(old('nama_depan')) ?>">

<!-- NAMA BELAKANG -->
<input class="form-control mb-2"
       name="nama_belakang"
       placeholder="Nama Belakang"
       value="<?= esc(old('nama_belakang')) ?>">

<!-- NAMA PANGGILAN -->
<input class="form-control mb-2"
       name="panggilan"
       placeholder="Nama Panggilan (opsional)"
       value="<?= esc(old('panggilan')) ?>">

<input class="form-control mb-2"
       name="gereja_asal"
       placeholder="Gereja Asal (opsional)"
       value="<?= esc(old('gereja_asal')) ?>">

<select name="unity" class="form-control mb-2">
  <option value="">Pilih Unity (opsional)</option>
  <?php foreach (($unityOptions ?? []) as $opt): ?>
    <option value="<?= esc($opt) ?>" <?= old('unity')===$opt?'selected':'' ?>>
      <?= esc($opt) ?>
    </option>
  <?php endforeach; ?>
</select>

<!-- TANGGAL LAHIR -->
<div class="form-group">
  <label class="small font-weight-bold">Tanggal Lahir</label>
  <div class="input-group">
    <div class="input-group-prepend">
      <span class="input-group-text">
        <i class="fas fa-calendar-alt"></i>
      </span>
    </div>
    <input type="date"
           name="tanggal_lahir"
           class="form-control"
           required
           value="<?= esc(old('tanggal_lahir')) ?>">
  </div>
</div>

<!-- JENIS KELAMIN -->
<select name="jenis_kelamin" class="form-control mb-2" required>
  <option value="">Jenis Kelamin</option>
  <option value="L" <?= old('jenis_kelamin')=='L'?'selected':'' ?>>Laki-laki</option>
  <option value="P" <?= old('jenis_kelamin')=='P'?'selected':'' ?>>Perempuan</option>
</select>

<!-- KELAS -->
<select name="kelas_id" class="form-control mb-2" required>
  <option value="">Pilih Kelas</option>
  <?php foreach ($kelas as $k): ?>
    <option value="<?= $k['id'] ?>"
      <?= old('kelas_id')==$k['id']?'selected':'' ?>>
      <?= esc($k['nama_kelas']) ?>
    </option>
  <?php endforeach; ?>
</select>

<!-- NO HP (OPSIONAL) -->
<input class="form-control mb-2"
       name="no_hp"
       placeholder="No WhatsApp Murid (opsional)"
       value="<?= esc(old('no_hp')) ?>">

<!-- ALAMAT (OPSIONAL) -->
<textarea class="form-control mb-2"
          name="alamat"
          placeholder="Alamat (opsional)"><?= esc(old('alamat')) ?></textarea>

<!-- FOTO -->
<label class="small">Foto Murid</label>
<input type="file" name="foto" class="form-control mb-3">

<!-- ACTION -->
<button class="btn btn-primary">
  💾 Simpan
</button>

<a href="<?= base_url('guru/murid') ?>" class="btn btn-secondary">
  Kembali
</a>

</form>

</div>
</div>
</section>

@endsection
