<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Yeni Firma (Tenant) Olustur
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow">
                <form method="POST" action="{{ route('central.tenants.store') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @csrf
                    <div>
                        <x-input-label for="company_name" value="Firma Adi" />
                        <x-text-input id="company_name" name="company_name" class="mt-1 block w-full" :value="old('company_name')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('company_name')" />
                    </div>

                    <div>
                        <x-input-label for="tenant_id" value="Tenant ID (opsiyonel)" />
                        <x-text-input id="tenant_id" name="tenant_id" class="mt-1 block w-full" :value="old('tenant_id')" placeholder="ornek-firma" />
                        <x-input-error class="mt-2" :messages="$errors->get('tenant_id')" />
                    </div>

                    <div>
                        <x-input-label for="domain" value="Domain" />
                        <x-text-input id="domain" name="domain" class="mt-1 block w-full" :value="old('domain')" placeholder="firma1.domain.com" required />
                        <x-input-error class="mt-2" :messages="$errors->get('domain')" />
                    </div>

                    <div>
                        <x-input-label for="default_currency" value="Varsayilan Para Birimi" />
                        <select id="default_currency" name="default_currency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            @foreach (['TRY', 'GBP', 'EUR', 'USD'] as $currency)
                                <option value="{{ $currency }}" @selected(old('default_currency', 'TRY') === $currency)>
                                    {{ $currency }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('default_currency')" />
                    </div>

                    @if (auth()->user()->isSuperAdmin())
                        <div class="md:col-span-2">
                            <x-input-label for="reseller_id" value="Bayi" />
                            <select id="reseller_id" name="reseller_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Bayi secilmedi</option>
                                @foreach ($resellers as $reseller)
                                    <option value="{{ $reseller->id }}" @selected((string) old('reseller_id') === (string) $reseller->id)>
                                        {{ $reseller->name }} ({{ $reseller->username }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('reseller_id')" />
                        </div>
                    @endif

                    <div class="md:col-span-2 flex items-center gap-3">
                        <x-primary-button>Firma Olustur</x-primary-button>
                        <a href="{{ route('central.tenants.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Iptal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
