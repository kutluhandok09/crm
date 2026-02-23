<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append([
            \App\Http\Middleware\ForceRequestRootUrl::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Keep auth redirects host-agnostic to avoid localhost leaks from APP_URL.
        $middleware->redirectGuestsTo(static fn (Request $request): string => '/login');

        $middleware->alias([
            'central.domain' => \App\Http\Middleware\EnsureCentralDomain::class,
            'tenant.access' => \App\Http\Middleware\EnsureTenantAccess::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TenantCouldNotBeIdentifiedOnDomainException $e, Request $request) {
            $preferredCentralDomain = collect(config('tenancy.central_domains', []))
                ->map(fn (string $domain): string => strtolower(trim($domain)))
                ->first(fn (string $domain): bool => ! in_array($domain, ['127.0.0.1', 'localhost'], true));

            $fallbackHost = $preferredCentralDomain ?: $request->getHost();
            $scheme = $request->isSecure() ? 'https' : 'http';
            $target = "{$scheme}://{$fallbackHost}/login";

            return redirect()->to($target);
        });
    })->create();
