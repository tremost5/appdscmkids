<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE absensi ADD COLUMN IF NOT EXISTS keterangan TEXT NULL AFTER lokasi_text");

        $requiredLocations = ['NICC', 'GRASA', 'CPM', 'Lokasi Lainnya'];
        foreach ($requiredLocations as $locationName) {
            $exists = DB::table('lokasi_ibadah')->where('nama_lokasi', $locationName)->exists();
            if (!$exists) {
                DB::table('lokasi_ibadah')->insert(['nama_lokasi' => $locationName]);
            }
        }

        $lainnyaId = (int) DB::table('lokasi_ibadah')
            ->where('nama_lokasi', 'Lokasi Lainnya')
            ->value('id');

        $normalizedIds = DB::table('lokasi_ibadah')
            ->whereIn('nama_lokasi', ['NICC', 'GRASA', 'CPM', 'Lokasi Lainnya'])
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        $legacyRows = DB::table('lokasi_ibadah')
            ->select('id', 'nama_lokasi')
            ->whereNotIn('id', $normalizedIds)
            ->get();

        foreach ($legacyRows as $row) {
            $legacyId = (int) $row->id;
            $legacyName = trim((string) $row->nama_lokasi);

            DB::table('absensi')
                ->where('lokasi_id', $legacyId)
                ->update([
                    'lokasi_id' => $lainnyaId,
                    'lokasi_text' => 'Lokasi Lainnya',
                    'keterangan' => DB::raw("COALESCE(NULLIF(keterangan, ''), '".str_replace("'", "''", $legacyName)."')"),
                ]);

            $usageCount = (int) DB::table('absensi')->where('lokasi_id', $legacyId)->count();
            if ($usageCount === 0) {
                DB::table('lokasi_ibadah')->where('id', $legacyId)->delete();
            }
        }
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE absensi DROP COLUMN IF EXISTS keterangan");
    }
};
