<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';

    protected $allowedFields = [
    'role_id',
    'nama_depan',
    'nama_belakang',
    'username',
    'email',
    'password',
    'no_hp',
    'alamat',
    'tanggal_lahir',
    'foto',
    'status',
    'session_token',
    'last_login',
    'last_seen',
    'last_activity',
    'updated_at',
    'is_locked',

    // 🔥 TAMBAHKAN INI
    'reset_token',
    'reset_expires',
];

}
