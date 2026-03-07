<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use Config\Database;

class UserRole extends BaseController
{
    protected $db;

    public function __construct(){
        parent::__construct();
        $this->db = Database::connect();
    }

    public function index()
    {
        $users = $this->db->table('users')
            ->select('id,nama_depan,nama_belakang,role_id')
            ->orderBy('nama_depan')
            ->get()->getResultArray();

        return view('superadmin/users/index', compact('users'));
    }

    public function update()
    {
        $id   = (int) $this->request->getPost('user_id');
        $role = (int) $this->request->getPost('role_id');
        $actorId = (int) session()->get('user_id');

        if (!in_array($role, [1, 2, 3], true)) {
            return redirect()->back()->with('error','Role tidak valid');
        }

        $user = $this->db->table('users')
            ->select('id, role_id, nama_depan, nama_belakang')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$user) {
            return redirect()->back()->with('error','User tidak ditemukan');
        }
        if ($actorId > 0 && $actorId === $id && $role !== 1) {
            return redirect()->back()->with('error','Tidak boleh menurunkan role akun superadmin yang sedang dipakai.');
        }
        if ((int) ($user['role_id'] ?? 0) === $role) {
            return redirect()->back()->with('warning','Role user tidak berubah');
        }

        $this->db->table('users')->where('id',$id)->update([
            'role_id'=>$role
        ]);

        if ($this->db->error()['code'] ?? 0) {
            return redirect()->back()->with('error','Gagal memperbarui role user');
        }

        systemLog(
            'UPDATE_ROLE',
            'Mengubah role user ID '.$id.' ke '.$role,
            'user',
            $id
        );
        logAudit('update_user_role', 'warning', [
            'target' => 'users',
            'target_id' => $id,
            'old' => ['role_id' => (int) ($user['role_id'] ?? 0)],
            'new' => ['role_id' => $role],
        ]);

        return redirect()->back()->with('success','Role diperbarui');
    }
}
