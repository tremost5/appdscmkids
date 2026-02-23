<?php

namespace App\Http\Middleware;

use Closure;
use Config\Database;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CiAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('isLoggedIn') || ! session('user_id')) {
            return redirect('/login');
        }

        $db = Database::connect();
        $user = $db->table('users')
            ->select('id, role_id, status, session_token')
            ->where('id', (int) session('user_id'))
            ->get()
            ->getRowArray();

        if (! $user || ($user['status'] ?? null) !== 'aktif') {
            session()->invalidate();
            session()->regenerateToken();
            return redirect('/login')->with('error', 'Sesi tidak valid. Silakan login kembali.');
        }

        $sessionToken = (string) session('session_token');
        $dbToken = (string) ($user['session_token'] ?? '');
        if ($sessionToken === '' || $dbToken === '' || ! hash_equals($dbToken, $sessionToken)) {
            session()->invalidate();
            session()->regenerateToken();
            return redirect('/login')->with('error', 'Sesi anda berakhir. Silakan login kembali.');
        }

        $maintenanceRow = $db->table('system_settings')
            ->select('value, setting_value')
            ->where('setting_key', 'maintenance_mode')
            ->get()
            ->getRowArray();

        $maintenanceValue = (string) ($maintenanceRow['value'] ?? ($maintenanceRow['setting_value'] ?? '0'));
        if ($maintenanceValue === '1' && (int) ($user['role_id'] ?? 0) !== 1) {
            session()->invalidate();
            session()->regenerateToken();
            return redirect('/login')->with('error', 'Sistem sedang maintenance. Coba lagi nanti.');
        }

        return $next($request);
    }
}
