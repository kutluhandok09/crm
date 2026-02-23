<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profil Bilgileri') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Email ve kullanici adi degistirilemez. Sadece ad soyad guncellenebilir.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.update', absolute: false) }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="username_readonly" :value="__('Kullanici Adi')" />
            <x-text-input id="username_readonly" type="text" class="mt-1 block w-full bg-gray-100" :value="$user->username" readonly disabled />
        </div>

        <div>
            <x-input-label for="email_readonly" :value="__('E-posta')" />
            <x-text-input id="email_readonly" type="email" class="mt-1 block w-full bg-gray-100" :value="$user->email" readonly disabled />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
