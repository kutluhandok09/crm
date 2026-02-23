<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCentralDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $centralDomains = collect(config('tenancy.central_domains', []))
            ->map(fn (string $domain): string => strtolower($domain))
            ->all();

        abort_unless(in_array($host, $centralDomains, true), 404);

        return $next($request);
    }
}
