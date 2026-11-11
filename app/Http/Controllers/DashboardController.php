<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        $route = match ($user->role) {
            'student' => 'dashboard.student',
            'supervisor' => 'dashboard.university',
            'organisation' => 'dashboard.organisation',
            'admin' => 'dashboard.admin',
            default => abort(403),
        };

        return redirect()->route($route);
    }
}
