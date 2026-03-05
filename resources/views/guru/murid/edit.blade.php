@extends('layouts/adminlte')
@section('content')

<section class="content-header">
<h1><?= isset($murid) ? 'Edit Murid' : 'Tambah Murid' ?></h1>
</section>

<section class="content">
<div class="card shadow-sm">
<div class="card-body">

<form method="post"
      enctype="multipart/form-data"
      action="<?= isset($murid)
        ? base_url('guru/murid/update/'.$murid['id'])
        : base_url('guru/murid/store') ?>">

<?= csrf_field() ?>

<input class="form-control mb-2" name="nama_depan"
 placeholder="Nama Depan" required
 value="<?= $murid['nama_depan'] ?? '' ?>">

<input class="form-control mb-2" name="nama_belakang"
 placeholder="Nama Belakang"
 value="<?= $murid['nama_belakang'] ?? '' ?>">

<!-- ✅ NAMA PANGGILAN -->
<input class="form-control mb-2" name="panggilan"
 placeholder="Nama Panggilan (opsional)"
 value="<?= $murid['panggilan'] ?? '' ?>">

<input class="form-control mb-2" name="gereja_asal"
 placeholder="Gereja Asal (opsional)"
 value="<?= esc($murid['gereja_asal'] ?? '') ?>">

<select name="unity" class="form-control mb-2">
<option value="">Pilih Unity (opsional)</option>
<?php foreach (($unityOptions ?? []) as $opt): ?>
<option value="<?= esc($opt) ?>" <?= (($murid['unity'] ?? '')===$opt)?'selected':'' ?>>
<?= esc($opt) ?>
</option>
<?php endforeach; ?>
</select>

<input type="date" class="form-control mb-2"
 name="tanggal_lahir" required
 value="<?= $murid['tanggal_lahir'] ?? '' ?>">

<select name="jenis_kelamin" class="form-control mb-2" required>
<option value="">Jenis Kelamin</option>
<option value="L" <?= (($murid['jenis_kelamin'] ?? '')=='L')?'selected':'' ?>>Laki-laki</option>
<option value="P" <?= (($murid['jenis_kelamin'] ?? '')=='P')?'selected':'' ?>>Perempuan</option>
</select>

<select name="kelas_id" class="form-control mb-2" required>
<option value="">Pilih Kelas</option>
<?php foreach ($kelas as $k): ?>
<option value="<?= $k['id'] ?>"
<?= (($murid['kelas_id'] ?? '')==$k['id'])?'selected':'' ?>>
<?= esc($k['nama_kelas']) ?>
</option>
<?php endforeach; ?>
</select>

<input class="form-control mb-2"
       name="no_hp"
       placeholder="No WhatsApp Murid (opsional)"
       value="<?= esc($murid['no_hp'] ?? '') ?>">

<textarea class="form-control mb-2"
 name="alamat" placeholder="Alamat(opsional)"><?= $murid['alamat'] ?? '' ?></textarea>

<label class="small">Foto Murid</label>
<input type="file" name="foto" class="form-control mb-3">

<button class="btn btn-primary">
💾 Simpan
</button>

<a href="<?= base_url('guru/murid') ?>"
 class="btn btn-secondary">Kembali</a>

</form>

</div>
</div>
</section>

@endsection
