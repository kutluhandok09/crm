<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Iki Adimli Dogrulama (2FA)
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Authenticator uygulamasi ile TOTP dogrulamasi etkinlestir.
        </p>
    </header>

    <div class="mt-4 space-y-4">
        @if (! $twoFactorEnabled)
            <form method="POST" action="{{ route('profile.two-factor.enable', absolute: false) }}">
                @csrf
                <x-primary-button>2FA Etkinlestir</x-primary-button>
            </form>
        @else
            <div class="rounded-md border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm text-gray-700">
                    Durum:
                    @if ($twoFactorConfirmed)
                        <span class="font-semibold text-emerald-700">Aktif</span>
                    @else
                        <span class="font-semibold text-amber-700">Onay Bekliyor</span>
                    @endif
                </p>
            </div>

            @if ($twoFactorQrCodeSvg)
                <div class="rounded-md border border-gray-200 p-4">
                    <p class="text-sm text-gray-700">QR kodunu Authenticator uygulamasi ile okut:</p>
                    <div class="mt-3 inline-block rounded-md bg-white p-2">
                        {!! $twoFactorQrCodeSvg !!}
                    </div>
                </div>
            @endif

            @if (! $twoFactorConfirmed)
                <form method="POST" action="{{ route('profile.two-factor.confirm', absolute: false) }}" class="space-y-3">
                    @csrf
                    <div>
                        <x-input-label for="code" value="Authenticator Kodu" />
                        <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" required />
                        <x-input-error class="mt-2" :messages="$errors->get('code')" />
                    </div>
                    <x-primary-button>2FA Onayla</x-primary-button>
                </form>
            @endif

            <div class="rounded-md border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-900">Recovery Codes</p>
                    <form method="POST" action="{{ route('profile.two-factor.recovery-codes', absolute: false) }}">
                        @csrf
                        <button class="text-sm font-semibold text-indigo-600 hover:text-indigo-500" type="submit">
                            Yenile
                        </button>
                    </form>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
                    @forelse ($recoveryCodes as $code)
                        <code class="rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-800">{{ $code }}</code>
                    @empty
                        <p class="text-sm text-gray-500">Henuz kod olusturulmadi.</p>
                    @endforelse
                </div>
            </div>

            <form method="POST" action="{{ route('profile.two-factor.disable', absolute: false) }}" class="space-y-3">
                @csrf
                <div>
                    <x-input-label for="current_password" value="2FA kapatmak icin mevcut sifre" />
                    <x-text-input id="current_password" name="current_password" type="password" class="mt-1 block w-full" required />
                    <x-input-error class="mt-2" :messages="$errors->get('current_password')" />
                </div>
                <button type="submit" class="inline-flex items-center rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">
                    2FA Devre Disi Birak
                </button>
            </form>
        @endif
    </div>
</section>
