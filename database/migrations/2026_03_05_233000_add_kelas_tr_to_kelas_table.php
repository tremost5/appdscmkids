<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('kelas')->where('kode_kelas', 'TR')->exists();
        if ($exists) {
            return;
        }

        DB::table('kelas')->insert([
            'tingkat_id'  => null,
            'kode_kelas'  => 'TR',
            'nama_kelas'  => 'Kelas TR',
        ]);
    }

    public function down(): void
    {
        $kelas = DB::table('kelas')->where('kode_kelas', 'TR')->first();
        if (!$kelas) {
            return;
        }

        $dipakai = DB::table('murid')->where('kelas_id', $kelas->id)->exists();
        if ($dipakai) {
            return;
        }

        DB::table('kelas')->where('id', $kelas->id)->delete();
    }
};
