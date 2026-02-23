<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Hesabin icin iki adimli dogrulama aktif. Authenticator uygulamandaki kodu veya kurtarma kodunu gir.
    </div>

    <form method="POST" action="{{ route('two-factor.challenge', absolute: false) }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="code" value="Authenticator Kodu" />
            <x-text-input id="code" type="text" name="code" class="mt-1 block w-full" autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="recovery_code" value="veya Kurtarma Kodu" />
            <x-text-input id="recovery_code" type="text" name="recovery_code" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('recovery_code')" class="mt-2" />
        </div>

        <div>
            <x-primary-button>Dogrula</x-primary-button>
        </div>
    </form>
</x-guest-layout>
