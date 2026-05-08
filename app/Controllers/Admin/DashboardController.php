<?php

namespace App\Controllers\Admin;

use App\Core\Middleware;
use App\Core\View;
use App\Models\Stats;

class DashboardController
{
    public function index(): void
    {
        Middleware::auth();
        $user = current_user();

        View::render('admin/dashboard', [
            'stats' => Stats::dashboard($user),
            'showsAllLogs' => ($user['role_slug'] ?? '') === 'master',
        ]);
    }
}
