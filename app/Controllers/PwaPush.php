<?php

namespace App\Controllers;

use App\Services\PwaPushService;

class PwaPush extends BaseController
{
    public function config(PwaPushService $pushService)
    {
        return $this->response->setJSON([
            'enabled' => $pushService->isConfigured(),
            'publicKey' => $pushService->publicKey(),
        ]);
    }

    public function subscribe(PwaPushService $pushService)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $payload = (array) ($this->request->getJSON(true) ?? []);

        try {
            $result = $pushService->upsertSubscription(
                (int) session('user_id'),
                (int) session('role_id'),
                $payload['subscription'] ?? [],
                $this->request->getUserAgent()->getAgentString()
            );
        } catch (\InvalidArgumentException $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Subscribe PWA gagal: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Gagal menyimpan subscription notifikasi.',
            ]);
        }

        return $this->response->setJSON([
            'status' => 'ok',
            'message' => 'Notifikasi berhasil diaktifkan.',
            'device' => $result['device_label'] ?? null,
        ]);
    }

    public function unsubscribe(PwaPushService $pushService)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $payload = (array) ($this->request->getJSON(true) ?? []);
        $endpoint = trim((string) ($payload['endpoint'] ?? ''));
        if ($endpoint === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Endpoint subscription kosong.',
            ]);
        }

        $pushService->unsubscribe((int) session('user_id'), $endpoint);

        return $this->response->setJSON([
            'status' => 'ok',
            'message' => 'Subscription notifikasi dilepas.',
        ]);
    }
}
