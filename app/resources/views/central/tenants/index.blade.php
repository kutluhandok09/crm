<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Firma (Tenant) Yonetimi
            </h2>
            <a href="{{ route('central.tenants.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">
                Yeni Firma
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Tenant ID</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Firma Adi</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Domain</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Bayi</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Islem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($tenants as $tenant)
                                <tr>
                                    <td class="px-4 py-2 text-gray-700">{{ $tenant->id }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ data_get($tenant->data, 'company_name', '-') }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ optional($tenant->domains->first())->domain ?? '-' }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $tenant->reseller?->name ?? '-' }}</td>
                                    <td class="px-4 py-2">
                                        <a class="text-indigo-600 hover:text-indigo-500" href="{{ route('central.tenants.show', $tenant) }}">
                                            Detay
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">Firma bulunamadi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $tenants->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
