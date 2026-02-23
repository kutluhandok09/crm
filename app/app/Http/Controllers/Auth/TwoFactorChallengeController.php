<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactorService,
    ) {
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('two_factor_login.user_id')) {
            return redirect('/login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['nullable', 'digits:6'],
            'recovery_code' => ['nullable', 'string', 'max:32'],
        ]);

        $userId = $request->session()->get('two_factor_login.user_id');
        $remember = (bool) $request->session()->get('two_factor_login.remember', false);

        /** @var User|null $user */
        $user = User::query()->find($userId);
        if (! $user) {
            $request->session()->forget('two_factor_login');

            return redirect('/login');
        }

        $valid = false;

        if ($request->filled('code')) {
            $valid = $this->verifyOtpCode($user, (string) $request->input('code'));
        } elseif ($request->filled('recovery_code')) {
            $valid = $this->consumeRecoveryCode($user, (string) $request->input('recovery_code'));
        }

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => 'Girilen dogrulama kodu gecersiz.',
            ]);
        }

        Auth::login($user, $remember);
        $request->session()->forget('two_factor_login');
        $request->session()->regenerate();

        return redirect()->intended($this->defaultRedirectPath($request));
    }

    protected function verifyOtpCode(User $user, string $code): bool
    {
        $secret = $this->decryptValue($user->two_factor_secret);

        if (! $secret) {
            return false;
        }

        return $this->twoFactorService->verifyCode($secret, trim($code));
    }

    protected function consumeRecoveryCode(User $user, string $recoveryCode): bool
    {
        $codes = $this->getRecoveryCodes($user);
        $needle = strtoupper(trim($recoveryCode));
        $matchedIndex = array_search($needle, $codes, true);

        if ($matchedIndex === false) {
            return false;
        }

        unset($codes[$matchedIndex]);
        $user->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode(array_values($codes))),
        ])->save();

        return true;
    }

    /**
     * @return list<string>
     */
    protected function getRecoveryCodes(User $user): array
    {
        $raw = $this->decryptValue($user->two_factor_recovery_codes);
        if (! $raw) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }

    protected function decryptValue(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
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
