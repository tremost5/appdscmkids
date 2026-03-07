@extends('layouts/adminlte')
@section('content')

<section class="content-header">
  <h1>👥 Manajemen Role User</h1>
</section>

<section class="content">
<div class="container-fluid">

<div class="card shadow-sm">
  <div class="card-body table-responsive">

    <table class="table table-striped">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Role</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($users as $u): ?>
        <tr>
          <td><?= esc($u['nama_depan'].' '.$u['nama_belakang']) ?></td>
          <td>
            <form method="post"
                  action="<?= base_url('superadmin/users/update') ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <select name="role_id" class="form-control">
                <option value="1" <?= (int)$u['role_id'] === 1 ? 'selected' : '' ?>>Superadmin</option>
                <option value="2" <?= (int)$u['role_id'] === 2 ? 'selected' : '' ?>>Admin</option>
                <option value="3" <?= (int)$u['role_id'] === 3 ? 'selected' : '' ?>>Guru</option>
              </select>
          </td>
          <td>
              <button class="btn btn-primary btn-sm">Update</button>
            </form>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>

  </div>
</div>

</div>
</section>

@endsection
