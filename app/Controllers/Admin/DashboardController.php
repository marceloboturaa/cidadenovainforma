<?php

namespace App\Controllers\Admin;

use App\Core\Middleware;
use App\Core\View;
use App\Models\Education;
use App\Models\Stats;

class DashboardController
{
    public function index(): void
    {
        Middleware::auth();
        $user = current_user();
        $roleSlugs = array_values(array_filter(explode(',', (string) ($user['role_slugs'] ?? $user['role_slug'] ?? ''))));
        $isStudent = in_array('estudante', $roleSlugs, true);
        $canViewSensitiveDashboard = Stats::canViewSensitiveInfo($user);
        $canViewEditorialDashboard = $canViewSensitiveDashboard || Stats::canViewEditorialInfo($user);

        View::render('admin/dashboard', [
            'stats' => Stats::dashboard($user),
            'showsAllLogs' => $canViewSensitiveDashboard,
            'canViewSensitiveDashboard' => $canViewSensitiveDashboard,
            'canViewEditorialDashboard' => $canViewEditorialDashboard,
            'isStudent' => $isStudent,
            'studentResponses' => $isStudent ? Education::studentResponsesForDashboard((int) ($user['id'] ?? 0)) : [],
        ]);
    }
}
