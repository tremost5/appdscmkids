<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $isLocalHost = str_starts_with($host, '127.0.0.1') || str_starts_with($host, 'localhost');

        if (app()->environment('local') || $isLocalHost) {
            return redirect()->to('/login');
        }

        return view('home/index');
    }
}
