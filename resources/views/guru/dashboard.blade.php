@extends('layouts/pwa')
@section('content')

<div class="card">
  <h2>👋 Halo, <?= esc(session('nama')) ?></h2>
  <p>Siap melakukan presensi hari ini</p>
</div>

<div class="card">
  <button class="btn">📍 Presensi Sekarang</button>
</div>

@endsection
