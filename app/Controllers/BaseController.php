<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class BaseController extends Controller
{
    protected array $helpers = ['url', 'form', 'kelas'];
    private array $columnExistsCache = [];

    // 🔥 GLOBAL DATA UNTUK VIEW (AMAN)
    protected array $globalViewData = [];

    protected function requireLogin()
    {
        if (! session()->get('user_id') || ! session()->get('role_id')) {
            return redirect()->to('/logout');
        }
        return null;
    }

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);

        /* ===============================
           UPDATE last_seen (TETAP)
        =============================== */
        if (session()->has('user_id')) {
            try {
                \Config\Database::connect()
                    ->table('users')
                    ->where('id', session()->get('user_id'))
                    ->update([
                        'last_seen' => date('Y-m-d H:i:s')
                    ]);
            } catch (\Throwable $e) {
                log_message('error', $e->getMessage());
            }
        }

        /* ===============================
           GLOBAL DATA ADMIN (TANPA VIEW)
        =============================== */
        if (session()->get('role_id') == 2) {
            try {
                $this->globalViewData['dobelHariIni'] =
                    \Config\Database::connect()
                        ->table('absensi_detail')
                        ->where('tanggal', date('Y-m-d'))
                        ->where('status', 'dobel')
                        ->countAllResults();
            } catch (\Throwable $e) {
                log_message('error', $e->getMessage());
                $this->globalViewData['dobelHariIni'] = 0;
            }
        }
    }

    protected function hasTableColumn(string $table, string $column): bool
    {
        $key = strtolower($table . '.' . $column);
        if (array_key_exists($key, $this->columnExistsCache)) {
            return $this->columnExistsCache[$key];
        }

        try {
            $row = \Config\Database::connect()
                ->query("SHOW COLUMNS FROM `{$table}` LIKE ?", [$column])
                ->getRowArray();
            $exists = !empty($row);
        } catch (\Throwable $e) {
            log_message('error', $e->getMessage());
            $exists = false;
        }

        $this->columnExistsCache[$key] = $exists;
        return $exists;
    }
}
