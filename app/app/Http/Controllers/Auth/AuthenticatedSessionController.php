<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        if ($user && $user->two_factor_secret && $user->two_factor_confirmed_at) {
            $request->session()->put('two_factor_login', [
                'user_id' => $user->getKey(),
                'remember' => $request->boolean('remember'),
            ]);

            Auth::logout();

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        return redirect()->intended($this->defaultRedirectPath($request));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    protected function defaultRedirectPath(Request $request): string
    {
        $host = strtolower($request->getHost());
        $centralDomains = collect(config('tenancy.central_domains'))
            ->map(fn (string $domain): string => strtolower($domain))
            ->all();

        if (in_array($host, $centralDomains, true)) {
            return route('dashboard', absolute: false);
        }

        return route('tenant.dashboard', absolute: false);
    }
}
