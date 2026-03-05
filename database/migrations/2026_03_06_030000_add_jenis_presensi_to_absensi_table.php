<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('absensi')) {
            return;
        }

        Schema::table('absensi', function (Blueprint $table): void {
            if (!Schema::hasColumn('absensi', 'jenis_presensi')) {
                $table->string('jenis_presensi', 20)
                    ->default('reguler')
                    ->after('lokasi_text');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('absensi')) {
            return;
        }

        Schema::table('absensi', function (Blueprint $table): void {
            if (Schema::hasColumn('absensi', 'jenis_presensi')) {
                $table->dropColumn('jenis_presensi');
            }
        });
    }
};

