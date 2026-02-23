<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\Security\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactorService,
    ) {
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $decryptedSecret = $this->decryptValue($user->two_factor_secret);
        $qrCodeSvg = null;

        if ($decryptedSecret) {
            $qrCodeSvg = $this->twoFactorService->makeQrCodeSvg(
                $this->twoFactorService->getOtpAuthUrl($user, $decryptedSecret)
            );
        }

        return view('profile.edit', [
            'user' => $user,
            'twoFactorEnabled' => (bool) $user->two_factor_secret,
            'twoFactorConfirmed' => (bool) $user->two_factor_confirmed_at,
            'twoFactorQrCodeSvg' => $qrCodeSvg,
            'recoveryCodes' => $this->getRecoveryCodes($user),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();
        $secret = $this->twoFactorService->generateSecret();
        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => null,
        ])->save();

        return Redirect::route('profile.edit')->with('status', 'two-factor-enabled');
    }

    public function confirmTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();
        $secret = $this->decryptValue($user->two_factor_secret);

        if (! $secret || ! $this->twoFactorService->verifyCode($secret, (string) $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => 'Dogrulama kodu gecersiz.',
            ]);
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        return Redirect::route('profile.edit')->with('status', 'two-factor-confirmed');
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return Redirect::route('profile.edit')->with('status', 'two-factor-disabled');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $codes = $this->twoFactorService->generateRecoveryCodes();

        $request->user()->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes)),
        ])->save();

        return Redirect::route('profile.edit')->with('status', 'recovery-codes-regenerated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * @return list<string>
     */
    protected function getRecoveryCodes($user): array
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
}
