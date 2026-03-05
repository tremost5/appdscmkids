<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('lokasi_ibadah')
            ->whereRaw('LOWER(nama_lokasi) = ?', ['online'])
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('lokasi_ibadah')->insert([
            'nama_lokasi' => 'Online',
        ]);
    }

    public function down(): void
    {
        $lokasi = DB::table('lokasi_ibadah')
            ->whereRaw('LOWER(nama_lokasi) = ?', ['online'])
            ->first();

        if (!$lokasi) {
            return;
        }

        $dipakai = DB::table('absensi')->where('lokasi_id', $lokasi->id)->exists();
        if ($dipakai) {
            return;
        }

        DB::table('lokasi_ibadah')->where('id', $lokasi->id)->delete();
    }
};
