<?php

use App\Http\Controllers\Central\DashboardController;
use App\Http\Controllers\Central\ResellerController;
use App\Http\Controllers\Central\TenantController;
use App\Http\Controllers\Central\TenantUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('central.domain')->group(function () {
    Route::get('/', function () {
        return auth()->check()
            ? redirect()->route('dashboard')
            : redirect()->route('login');
    })->name('home');

    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::prefix('central')->name('central.')->group(function () {
            Route::middleware('role:super-admin')->group(function () {
                Route::get('/resellers', [ResellerController::class, 'index'])->name('resellers.index');
                Route::post('/resellers', [ResellerController::class, 'store'])->name('resellers.store');
            });

            Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
            Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
            Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
            Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
            Route::post('/tenants/{tenant}/users', [TenantUserController::class, 'store'])->name('tenants.users.store');
        });
    });
});

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
