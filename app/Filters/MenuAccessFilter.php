<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MenuAccessFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session('isLoggedIn')) {
            return null;
        }

        $role = (int) session('role_id');
        $path = trim($request->getUri()->getPath(), '/');

        // superadmin tidak dibatasi toggle menu
        if ($role === 1 || $path === '') {
            return null;
        }

        $blocked = false;
        $redirect = '/dashboard';

        if ($role === 3) {
            $redirect = '/dashboard/guru';

            if (str_starts_with($path, 'guru/absensi') || str_starts_with($path, 'guru/absensi-hari-ini') || str_starts_with($path, 'guru/unity')) {
                $blocked = (int) setting('guru_absen', 1) !== 1;
            } elseif (str_starts_with($path, 'guru/murid')) {
                $blocked = (int) setting('guru_murid', 1) !== 1;
            } elseif (str_starts_with($path, 'guru/materi')) {
                $blocked = (int) setting('guru_materi', 1) !== 1;
            } elseif (str_starts_with($path, 'guru/kegiatan')) {
                $blocked = (int) setting('guru_kegiatan', 1) !== 1;
            }
        }

        if ($role === 2) {
            $redirect = '/dashboard/admin';

            if (
                str_starts_with($path, 'admin/rekap-absensi')
                || str_starts_with($path, 'admin/absensi-dobel')
                || str_starts_with($path, 'admin/statistik')
                || str_starts_with($path, 'admin/laporan-point')
            ) {
                $blocked = (int) setting('admin_absen', 1) !== 1;
            } elseif (str_starts_with($path, 'admin/naik-kelas')) {
                $blocked = (int) setting('admin_naik_kelas', 1) !== 1;
            } elseif (str_starts_with($path, 'admin/bahan-ajar')) {
                $blocked = (int) setting('admin_materi', 1) !== 1;
            } elseif (str_starts_with($path, 'admin/guru')) {
                $blocked = (int) setting('admin_guru', 1) !== 1;
            }
        }

        if ($blocked) {
            return redirect()->to($redirect)->with('error', 'Menu sedang dinonaktifkan oleh superadmin.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
