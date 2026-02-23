<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $visibleTenantIds = Tenant::query()
            ->visibleTo($user)
            ->pluck('id');

        $assignedCompanyUsers = User::query()
            ->whereHas('companyTenants', fn ($query) => $query->whereIn('tenant_id', $visibleTenantIds))
            ->count();

        return view('central.dashboard', [
            'tenantCount' => $visibleTenantIds->count(),
            'resellerCount' => $user->isSuperAdmin()
                ? User::role('reseller')->count()
                : 0,
            'companyUserCount' => $assignedCompanyUsers,
        ]);
    }
}
