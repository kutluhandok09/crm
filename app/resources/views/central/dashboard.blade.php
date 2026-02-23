<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Merkezi Panel
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-sm text-gray-500">Gorunen Firma (Tenant)</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $tenantCount }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-sm text-gray-500">Bayi Sayisi</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $resellerCount }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-sm text-gray-500">Firma Kullanici Sayisi</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $companyUserCount }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
