<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use Config\Database;

class WaTemplate extends BaseController
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::connect();
        helper(['wa', 'wa_template']);
    }

    public function index()
    {
        waRecipientEnsureSchema();
        $defaults = waTemplateDefaults();
        $templates = [];
        foreach ($defaults as $key => $value) {
            $templates[$key] = waTemplateGet($key, $value);
        }

        $users = $this->db->table('users')
            ->select('id, role_id, nama_depan, nama_belakang, no_hp, status')
            ->whereIn('role_id', [1, 2])
            ->orderBy('role_id', 'ASC')
            ->orderBy('nama_depan', 'ASC')
            ->get()
            ->getResultArray();

        $recipientRows = $this->db->table('wa_recipients')
            ->select('user_id, is_active')
            ->whereIn('role_id', [1, 2])
            ->get()
            ->getResultArray();

        $recipientMap = [];
        foreach ($recipientRows as $row) {
            $recipientMap[(int) $row['user_id']] = (int) ($row['is_active'] ?? 0) === 1;
        }

        return view('superadmin/wa_template/index', [
            'templates' => $templates,
            'users' => $users,
            'recipientMap' => $recipientMap,
            'placeholders' => [
                '{nama_lengkap}', '{nama_depan}', '{nama_belakang}',
                '{username}', '{no_hp}', '{status}',
            ],
        ]);
    }

    public function save()
    {
        waRecipientEnsureSchema();
        $templateKeys = array_keys(waTemplateDefaults());
        foreach ($templateKeys as $key) {
            $text = trim((string) $this->request->getPost($key));
            if ($text === '') {
                $text = waTemplateDefaults()[$key];
            }
            waTemplateUpsert($key, $text);
        }

        $selectedIds = array_values(array_unique(array_map('intval', (array) $this->request->getPost('recipient_ids'))));
        $selectedMap = [];
        foreach ($selectedIds as $id) {
            if ($id > 0) {
                $selectedMap[$id] = true;
            }
        }

        $users = $this->db->table('users')
            ->select('id, role_id, no_hp')
            ->whereIn('role_id', [1, 2])
            ->get()
            ->getResultArray();

        $skippedNoHp = 0;
        foreach ($users as $user) {
            $userId = (int) ($user['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $noHp = trim((string) ($user['no_hp'] ?? ''));
            $noHp = $noHp !== '' ? (formatWA($noHp) ?: '') : '';
            $isSelected = isset($selectedMap[$userId]);

            $exists = $this->db->table('wa_recipients')
                ->where('user_id', $userId)
                ->get()
                ->getRowArray();

            if ($isSelected && $noHp === '') {
                $skippedNoHp++;
            }

            if ($exists) {
                $updateData = [
                    'role_id' => (int) ($user['role_id'] ?? 0),
                    'is_active' => ($isSelected && $noHp !== '') ? 1 : 0,
                ];
                if ($noHp !== '') {
                    $updateData['no_hp'] = $noHp;
                }
                $this->db->table('wa_recipients')
                    ->where('user_id', $userId)
                    ->update($updateData);
                continue;
            }

            if ($noHp !== '') {
                $this->db->table('wa_recipients')->insert([
                    'user_id' => $userId,
                    'role_id' => (int) ($user['role_id'] ?? 0),
                    'no_hp' => $noHp,
                    'is_active' => $isSelected ? 1 : 0,
                ]);
            }
        }

        $message = 'Template WA dan penerima notifikasi berhasil disimpan.';
        if ($skippedNoHp > 0) {
            $message .= ' Ada ' . $skippedNoHp . ' user dipilih tetapi no_hp kosong/tidak valid.';
        }

        return redirect()->back()->with('success', $message);
    }
}
