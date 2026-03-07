<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Config\Database;

class AuditLog extends BaseController
{
    protected $db;

    public function __construct(){
        parent::__construct();
        $this->db = Database::connect();
    }

    /* =========================
       LIST AUDIT LOG
    ========================= */
    public function index()
    {
        $start = $this->request->getGet('start');
        $end   = $this->request->getGet('end');
        $only  = $this->request->getGet('alert');

        $builder = $this->db->table('audit_log al')
            ->select('
                al.*,
                u.nama_depan,
                u.nama_belakang
            ')
            ->join('users u', 'u.id = al.user_id', 'left')
            ->orderBy('al.created_at', 'DESC');

        if ($start && $end) {
            $builder
                ->where('al.created_at >=', $start . ' 00:00:00')
                ->where('al.created_at <=', $end . ' 23:59:59');
        }

        if ($only) {
            $builder->whereIn('al.severity', ['warning', 'critical']);
        }

        return view('admin/audit_log', [
            'logs'  => $builder->get()->getResultArray(),
            'start' => $start,
            'end'   => $end,
            'only'  => $only
        ]);
    }

    /* =========================
       DETAIL AUDIT
    ========================= */
    public function detail($id)
    {
        $row = $this->db->table('audit_log al')
            ->select('
                al.*,
                u.nama_depan,
                u.nama_belakang
            ')
            ->join('users u', 'u.id = al.user_id', 'left')
            ->where('al.id', $id)
            ->get()
            ->getRowArray();

        if (!$row) {
            return redirect()->to(base_url('admin/audit-log'));
        }

        return view('admin/audit_log_detail', [
            'row' => $row
        ]);
    }

    /* =========================
       EXPORT PDF
    ========================= */
    public function exportPdf()
    {
        $start = $this->request->getGet('start');
        $end   = $this->request->getGet('end');

        if (!$start || !$end) {
            return redirect()->back()->with('error', 'Pilih rentang tanggal');
        }

        $logs = $this->db->table('audit_log al')
            ->select('
                al.*,
                u.nama_depan,
                u.nama_belakang
            ')
            ->join('users u', 'u.id = al.user_id', 'left')
            ->where('al.created_at >=', $start . ' 00:00:00')
            ->where('al.created_at <=', $end . ' 23:59:59')
            ->orderBy('al.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return view('admin/audit_logpdf', [
            'logs'  => $logs,
            'start' => $start,
            'end'   => $end
        ]);
    }
}
