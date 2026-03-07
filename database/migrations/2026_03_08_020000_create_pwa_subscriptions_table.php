<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pwa_subscriptions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('role_id');
            $table->text('endpoint');
            $table->text('public_key');
            $table->text('auth_token');
            $table->string('content_encoding', 30)->default('aes128gcm');
            $table->string('user_agent', 255)->nullable();
            $table->string('device_label', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'endpoint'], 'pwa_subscriptions_user_endpoint_unique');
            $table->index(['role_id', 'is_active'], 'pwa_subscriptions_role_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pwa_subscriptions');
    }
};
