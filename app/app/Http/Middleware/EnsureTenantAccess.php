<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $currentTenant = tenant();

        abort_if(! $currentTenant, 404);
        abort_if(! $user, 403);

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if ($user->isReseller() && (int) $currentTenant->reseller_id === (int) $user->getKey()) {
            return $next($request);
        }

        $hasAccess = $user->companyTenants()
            ->where('tenant_id', (string) $currentTenant->getTenantKey())
            ->exists();

        abort_unless($hasAccess, 403);

        return $next($request);
    }
}
