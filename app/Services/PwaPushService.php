<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PwaPushService
{
    public function isEnabled(): bool
    {
        return (bool) config('pwa_push.enabled', true);
    }

    public function publicKey(): string
    {
        return trim((string) config('pwa_push.public_key', ''));
    }

    public function isConfigured(): bool
    {
        return $this->isEnabled()
            && trim((string) config('pwa_push.subject', '')) !== ''
            && $this->publicKey() !== ''
            && trim((string) config('pwa_push.private_key', '')) !== '';
    }

    public function upsertSubscription(int $userId, int $roleId, array $subscription, ?string $userAgent = null): array
    {
        if (!Schema::hasTable('pwa_subscriptions')) {
            throw new \RuntimeException('Tabel pwa_subscriptions belum tersedia.');
        }

        $endpoint = trim((string) ($subscription['endpoint'] ?? ''));
        $publicKey = trim((string) ($subscription['keys']['p256dh'] ?? ''));
        $authToken = trim((string) ($subscription['keys']['auth'] ?? ''));
        $contentEncoding = trim((string) ($subscription['contentEncoding'] ?? 'aes128gcm'));

        if ($endpoint === '' || $publicKey === '' || $authToken === '') {
            throw new \InvalidArgumentException('Subscription push tidak lengkap.');
        }

        $row = [
            'user_id' => $userId,
            'role_id' => $roleId,
            'public_key' => $publicKey,
            'auth_token' => $authToken,
            'content_encoding' => $contentEncoding ?: 'aes128gcm',
            'user_agent' => $this->trimForDb($userAgent, 255),
            'device_label' => $this->detectDeviceLabel((string) $userAgent),
            'is_active' => 1,
            'last_seen_at' => now(),
            'updated_at' => now(),
        ];

        $existing = DB::table('pwa_subscriptions')->where('endpoint', $endpoint)->first();
        if ($existing) {
            DB::table('pwa_subscriptions')->where('id', $existing->id)->update($row);
        } else {
            $row['endpoint'] = $endpoint;
            $row['created_at'] = now();
            DB::table('pwa_subscriptions')->insert($row);
        }

        return [
            'endpoint' => $endpoint,
            'device_label' => $row['device_label'] ?? null,
        ];
    }

    public function unsubscribe(int $userId, string $endpoint): void
    {
        if (!Schema::hasTable('pwa_subscriptions')) {
            return;
        }

        DB::table('pwa_subscriptions')
            ->where('user_id', $userId)
            ->where('endpoint', $endpoint)
            ->delete();
    }

    public function sendNewMateriNotification(array $materi): array
    {
        if (!Schema::hasTable('pwa_subscriptions')) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        }

        $targetRoles = config('pwa_push.target_role_ids', [3]);
        $subscriptions = DB::table('pwa_subscriptions')
            ->whereIn('role_id', $targetRoles)
            ->where('is_active', 1)
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();

        if ($subscriptions === []) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        }

        if (!$this->isConfigured()) {
            log_message('warning', 'PWA push dilewati karena VAPID belum dikonfigurasi.');
            return ['sent' => 0, 'failed' => 0, 'skipped' => count($subscriptions)];
        }

        $payload = json_encode([
            'title' => 'Materi Baru',
            'body' => trim(sprintf(
                '%s untuk kelas %s',
                (string) ($materi['judul'] ?? 'Materi baru tersedia'),
                (string) ($materi['nama_kelas'] ?? '-')
            )),
            'icon' => base_url('pwa/icons/icon-192.png'),
            'badge' => base_url('pwa/icons/icon-192.png'),
            'tag' => 'materi-' . (int) ($materi['id'] ?? 0),
            'url' => base_url('guru/materi'),
            'data' => [
                'materi_id' => (int) ($materi['id'] ?? 0),
                'kelas_id' => (int) ($materi['kelas_id'] ?? 0),
                'url' => base_url('guru/materi'),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => trim((string) config('pwa_push.subject')),
                'publicKey' => $this->publicKey(),
                'privateKey' => trim((string) config('pwa_push.private_key')),
            ],
        ], [
            'TTL' => 300,
            'urgency' => 'normal',
            'batchSize' => 100,
        ]);
        $webPush->setReuseVAPIDHeaders(true);

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => (string) ($subscription['endpoint'] ?? ''),
                    'publicKey' => (string) ($subscription['public_key'] ?? ''),
                    'authToken' => (string) ($subscription['auth_token'] ?? ''),
                    'contentEncoding' => (string) ($subscription['content_encoding'] ?? 'aes128gcm'),
                ]),
                $payload
            );
        }

        $sent = 0;
        $failed = 0;
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getEndpoint();
            if ($report->isSuccess()) {
                $sent++;
                DB::table('pwa_subscriptions')
                    ->where('endpoint', $endpoint)
                    ->update([
                        'last_seen_at' => now(),
                        'updated_at' => now(),
                    ]);
                continue;
            }

            $failed++;
            log_message('warning', 'PWA push gagal ke endpoint {endpoint}: {reason}', [
                'endpoint' => $endpoint,
                'reason' => $report->getReason(),
            ]);

            if ($report->isSubscriptionExpired()) {
                DB::table('pwa_subscriptions')->where('endpoint', $endpoint)->delete();
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'skipped' => 0];
    }

    private function detectDeviceLabel(string $userAgent): ?string
    {
        $userAgent = strtolower($userAgent);
        if ($userAgent === '') {
            return null;
        }

        return match (true) {
            str_contains($userAgent, 'android') => 'Android',
            str_contains($userAgent, 'iphone') => 'iPhone',
            str_contains($userAgent, 'ipad') => 'iPad',
            str_contains($userAgent, 'windows') => 'Windows',
            str_contains($userAgent, 'mac os') => 'Mac',
            default => 'Perangkat',
        };
    }

    private function trimForDb(?string $value, int $limit): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $limit);
    }
}
