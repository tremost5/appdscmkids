@extends('layouts/adminlte')
@section('content')

<section class="content-header">
  <h3>Edit Murid</h3>
</section>

<section class="content">
  <div class="card">
    <div class="card-body">
      <?php
        $uri = trim((string) uri_string(), '/');
        $updateUrl = str_starts_with($uri, 'dashboard/superadmin/')
          ? base_url('dashboard/superadmin/murid/update/'.$murid['id'])
          : base_url('admin/murid/update/'.$murid['id']);
        $backUrl = str_starts_with($uri, 'dashboard/superadmin/')
          ? base_url('dashboard/superadmin/murid')
          : base_url('admin/murid');
      ?>

      <form method="post" enctype="multipart/form-data" action="<?= $updateUrl ?>">
        <?= csrf_field() ?>

        <div class="form-group">
          <label>Nama Depan</label>
          <input class="form-control" name="nama_depan" required value="<?= esc(old('nama_depan', $murid['nama_depan'] ?? '')) ?>">
        </div>

        <div class="form-group">
          <label>Nama Belakang</label>
          <input class="form-control" name="nama_belakang" value="<?= esc(old('nama_belakang', $murid['nama_belakang'] ?? '')) ?>">
        </div>

        <div class="form-group">
          <label>Panggilan</label>
          <input class="form-control" name="panggilan" value="<?= esc(old('panggilan', $murid['panggilan'] ?? '')) ?>">
        </div>

        <div class="form-group">
          <label>Gereja Asal</label>
          <input class="form-control" name="gereja_asal" value="<?= esc(old('gereja_asal', $murid['gereja_asal'] ?? '')) ?>">
        </div>

        <div class="form-group">
          <label>Unity</label>
          <select name="unity" class="form-control">
            <option value="">Pilih Unity (opsional)</option>
            <?php foreach (($unityOptions ?? []) as $opt): ?>
              <option value="<?= esc($opt) ?>" <?= old('unity', $murid['unity'] ?? '') === $opt ? 'selected' : '' ?>>
                <?= esc($opt) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Tanggal Lahir</label>
          <input type="date" class="form-control" name="tanggal_lahir" required value="<?= esc(old('tanggal_lahir', $murid['tanggal_lahir'] ?? '')) ?>">
        </div>

        <div class="form-group">
          <label>Jenis Kelamin</label>
          <select name="jenis_kelamin" class="form-control" required>
            <?php $jk = old('jenis_kelamin', $murid['jenis_kelamin'] ?? ''); ?>
            <option value="">Pilih</option>
            <option value="L" <?= $jk === 'L' ? 'selected' : '' ?>>Laki-laki</option>
            <option value="P" <?= $jk === 'P' ? 'selected' : '' ?>>Perempuan</option>
          </select>
        </div>

        <div class="form-group">
          <label>Kelas</label>
          <select name="kelas_id" class="form-control" required>
            <option value="">Pilih Kelas</option>
            <?php $selectedKelas = (int) old('kelas_id', $murid['kelas_id'] ?? 0); ?>
            <?php foreach (($kelas ?? []) as $k): ?>
              <option value="<?= (int) $k['id'] ?>" <?= $selectedKelas === (int) $k['id'] ? 'selected' : '' ?>>
                <?= esc($k['nama_kelas']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>No WhatsApp</label>
          <input class="form-control" name="no_hp" value="<?= esc(old('no_hp', $murid['no_hp'] ?? '')) ?>">
        </div>

        <div class="form-group">
          <label>Alamat</label>
          <textarea class="form-control" name="alamat"><?= esc(old('alamat', $murid['alamat'] ?? '')) ?></textarea>
        </div>

        <div class="form-group">
          <label>Foto Murid</label>
          <input type="file" name="foto" class="form-control">
        </div>

        <button class="btn btn-primary">Simpan</button>
        <a href="<?= $backUrl ?>" class="btn btn-secondary">Kembali</a>
      </form>
    </div>
  </div>
</section>

@endsection
