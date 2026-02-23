<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Firma Paneli
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-lg bg-white p-6 shadow">
                <p class="text-sm text-gray-500">Aktif Tenant</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">{{ tenant('id') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <a href="{{ route('tenant.products.index') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm hover:border-indigo-300">
                    <p class="text-sm text-gray-500">Stok</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Urunleri Yonet</p>
                </a>
                <a href="{{ route('tenant.invoices.index') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm hover:border-indigo-300">
                    <p class="text-sm text-gray-500">Satis / Alis</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Faturalar</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
