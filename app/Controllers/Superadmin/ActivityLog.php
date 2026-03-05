<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use Config\Database;

class ActivityLog extends BaseController
{
    public function index()
    {
        $db    = Database::connect();
        $start = $this->request->getGet('start') ?: date('Y-m-d');
        $end   = $this->request->getGet('end') ?: date('Y-m-d');
        $role  = $this->request->getGet('role') ?: 'all';

        $builder = $db->table('audit_log al')
            ->select('al.*, u.nama_depan, u.nama_belakang, u.role_id')
            ->join('users u', 'u.id = al.user_id', 'left')
            ->where('al.created_at', '>=', $start.' 00:00:00')
            ->where('al.created_at', '<=', $end.' 23:59:59')
            ->orderBy('al.created_at', 'DESC');

        if ($role === 'admin') {
            $builder->where('u.role_id', 2);
        } elseif ($role === 'guru') {
            $builder->where('u.role_id', 3);
        } else {
            $builder->whereIn('u.role_id', [2, 3]);
        }

        $logs = $builder->limit(300)->get()->getResultArray();

        return view('superadmin/activity_log/index', [
            'logs'  => $logs,
            'start' => $start,
            'end'   => $end,
            'role'  => $role,
        ]);
    }
}
