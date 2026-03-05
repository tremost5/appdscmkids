<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('murid', function (Blueprint $table) {
            if (!Schema::hasColumn('murid', 'gereja_asal')) {
                $table->string('gereja_asal', 150)->nullable()->after('panggilan');
            }

            if (!Schema::hasColumn('murid', 'unity')) {
                $table->string('unity', 50)->nullable()->after('gereja_asal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('murid', function (Blueprint $table) {
            if (Schema::hasColumn('murid', 'unity')) {
                $table->dropColumn('unity');
            }

            if (Schema::hasColumn('murid', 'gereja_asal')) {
                $table->dropColumn('gereja_asal');
            }
        });
    }
};
