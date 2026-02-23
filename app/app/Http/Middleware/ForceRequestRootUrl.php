<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ForceRequestRootUrl
{
    /**
     * Force URL generation to the current request host.
     *
     * This prevents accidental redirects/links to localhost when APP_URL
     * is stale or cached with a local value.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        URL::forceRootUrl($request->getSchemeAndHttpHost());

        if ($request->isSecure()) {
            URL::forceScheme('https');
        }

        return $next($request);
    }
}
