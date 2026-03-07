<?php

namespace App\Models;

use CodeIgniter\Model;

class AbsensiModel extends Model
{
    protected $table = 'absensi';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'guru_id',
        'lokasi_id',
        'lokasi_text',
        'keterangan',
        'jenis_presensi',
        'tanggal',
        'jam',
        'selfie_foto'
    ];

    protected $useTimestamps = false;
}
