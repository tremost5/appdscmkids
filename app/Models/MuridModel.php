<?php

namespace App\Models;

use CodeIgniter\Model;

class MuridModel extends Model
{
    protected $table = 'murid';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'nama_depan',
        'nama_belakang',
        'panggilan',
        'gereja_asal',
        'unity',
        'kelas_id',
        'jenis_kelamin',
        'tanggal_lahir',
        'alamat',
        'no_hp',
        'foto',
        'status',
    ];

    protected $useTimestamps = false;
}
