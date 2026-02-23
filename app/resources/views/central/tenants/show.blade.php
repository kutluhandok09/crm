<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Firma Detayi: {{ data_get($tenant->data, 'company_name', $tenant->id) }}
            </h2>
            <a href="{{ route('central.tenants.index', absolute: false) }}" class="text-sm text-gray-600 hover:text-gray-900">Geri</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-lg bg-white p-6 shadow">
                <dl class="grid grid-cols-1 gap-4 md:grid-cols-2 text-sm">
                    <div>
                        <dt class="font-semibold text-gray-700">Tenant ID</dt>
                        <dd class="mt-1 text-gray-900">{{ $tenant->id }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-700">Bayi</dt>
                        <dd class="mt-1 text-gray-900">{{ $tenant->reseller?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-700">Domain</dt>
                        <dd class="mt-1 text-gray-900">
                            @foreach ($tenant->domains as $domain)
                                <div>{{ $domain->domain }}</div>
                            @endforeach
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-700">Varsayilan Para Birimi</dt>
                        <dd class="mt-1 text-gray-900">{{ data_get($tenant->data, 'default_currency', 'TRY') }}</dd>
                    </div>
                </dl>
            </div>

            @can('manageUsers', $tenant)
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="text-lg font-semibold text-gray-900">Firma Kullanici Ekle</h3>
                    <form method="POST" action="{{ route('central.tenants.users.store', $tenant, false) }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
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
                            <x-input-label for="role" value="Firma Ici Rol" />
                            <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="owner" @selected(old('role') === 'owner')>Owner</option>
                                <option value="manager" @selected(old('role') === 'manager')>Manager</option>
                                <option value="staff" @selected(old('role', 'staff') === 'staff')>Staff</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('role')" />
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
                            <x-primary-button>Kullanici Ekle</x-primary-button>
                        </div>
                    </form>
                </div>
            @endcan

            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900">Firma Kullanicilari</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Ad</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Kullanici Adi</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">E-posta</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Rol</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($tenant->users as $user)
                                <tr>
                                    <td class="px-4 py-2 text-gray-700">{{ $user->name }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $user->username }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $user->email }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $user->pivot->role }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">Bu firmaya atanmis kullanici yok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
