<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bayi Yonetimi
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900">Yeni Bayi Ekle</h3>
                <form method="POST" action="{{ route('central.resellers.store', absolute: false) }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    @csrf
                    <div>
                        <x-input-label for="name" value="Ad Soyad" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <x-input-label for="username" value="Kullanici Adi" />
                        <x-text-input id="username" name="username" class="mt-1 block w-full" :value="old('username')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('username')" />
                    </div>
                    <div>
                        <x-input-label for="email" value="E-posta" />
                        <x-text-input id="email" type="email" name="email" class="mt-1 block w-full" :value="old('email')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>
                    <div>
                        <x-input-label for="password" value="Sifre" />
                        <x-text-input id="password" type="password" name="password" class="mt-1 block w-full" required />
                        <x-input-error class="mt-2" :messages="$errors->get('password')" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" value="Sifre Tekrar" />
                        <x-text-input id="password_confirmation" type="password" name="password_confirmation" class="mt-1 block w-full" required />
                    </div>
                    <div class="md:col-span-2">
                        <x-primary-button>Bayi Olustur</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900">Mevcut Bayiler</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">ID</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Ad</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Kullanici Adi</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">E-posta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($resellers as $reseller)
                                <tr>
                                    <td class="px-4 py-2 text-gray-700">{{ $reseller->id }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $reseller->name }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $reseller->username }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $reseller->email }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">Bayi bulunamadi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $resellers->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
