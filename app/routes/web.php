<?php

use App\Http\Controllers\Central\DashboardController;
use App\Http\Controllers\Central\ResellerController;
use App\Http\Controllers\Central\TenantController;
use App\Http\Controllers\Central\TenantUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/**
 * Register central routes for each allowed central domain.
 *
 * Important: we only assign route names on the first (primary) domain.
 * Otherwise Laravel keeps the last duplicate name and URL generation may
 * unexpectedly point to localhost/another domain.
 */
$centralDomains = array_values(config('tenancy.central_domains', []));
$appUrlHost = parse_url((string) config('app.url'), PHP_URL_HOST);

$primaryCentralDomain = null;
if (is_string($appUrlHost) && in_array($appUrlHost, $centralDomains, true)) {
    $primaryCentralDomain = $appUrlHost;
} else {
    $nonLocalCentralDomains = array_values(array_filter(
        $centralDomains,
        static function (string $domain): bool {
            $normalized = strtolower(trim($domain));

            if (in_array($normalized, ['localhost', '127.0.0.1', '::1'], true)) {
                return false;
            }

            return filter_var($normalized, FILTER_VALIDATE_IP) === false;
        }
    ));

    $primaryCentralDomain = $nonLocalCentralDomains[0] ?? ($centralDomains[0] ?? null);
}

foreach ($centralDomains as $centralDomain) {
    $withNames = $centralDomain === $primaryCentralDomain;

    Route::domain($centralDomain)
        ->middleware('central.domain')
        ->group(function () use ($withNames) {
            $home = Route::get('/', function () {
                return auth()->check()
                    ? redirect('/dashboard')
                    : redirect()->route('login');
            });
            if ($withNames) {
                $home->name('home');
            }

            Route::middleware('auth')->group(function () use ($withNames) {
                $dashboard = Route::get('/dashboard', DashboardController::class);
                if ($withNames) {
                    $dashboard->name('dashboard');
                }

                Route::prefix('central')->group(function () use ($withNames) {
                    Route::middleware('role:super-admin')->group(function () use ($withNames) {
                        $resellersIndex = Route::get('/resellers', [ResellerController::class, 'index']);
                        $resellersStore = Route::post('/resellers', [ResellerController::class, 'store']);

                        if ($withNames) {
                            $resellersIndex->name('central.resellers.index');
                            $resellersStore->name('central.resellers.store');
                        }
                    });

                    $tenantsIndex = Route::get('/tenants', [TenantController::class, 'index']);
                    $tenantsCreate = Route::get('/tenants/create', [TenantController::class, 'create']);
                    $tenantsStore = Route::post('/tenants', [TenantController::class, 'store']);
                    $tenantsShow = Route::get('/tenants/{tenant}', [TenantController::class, 'show']);
                    $tenantUsersStore = Route::post('/tenants/{tenant}/users', [TenantUserController::class, 'store']);

                    if ($withNames) {
                        $tenantsIndex->name('central.tenants.index');
                        $tenantsCreate->name('central.tenants.create');
                        $tenantsStore->name('central.tenants.store');
                        $tenantsShow->name('central.tenants.show');
                        $tenantUsersStore->name('central.tenants.users.store');
                    }
                });
            });
        });
}

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/profile/two-factor/enable', [ProfileController::class, 'enableTwoFactor'])->name('profile.two-factor.enable');
    Route::post('/profile/two-factor/confirm', [ProfileController::class, 'confirmTwoFactor'])->name('profile.two-factor.confirm');
    Route::post('/profile/two-factor/disable', [ProfileController::class, 'disableTwoFactor'])->name('profile.two-factor.disable');
    Route::post('/profile/two-factor/recovery-codes', [ProfileController::class, 'regenerateRecoveryCodes'])->name('profile.two-factor.recovery-codes');
});

require __DIR__.'/auth.php';
