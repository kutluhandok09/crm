<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Urunler
            </h2>
            <a href="{{ route('tenant.products.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">
                Yeni Urun
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Urun</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">SKU</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Barkod</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Birim Fiyat</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">KDV</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Stok</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($products as $product)
                                <tr>
                                    <td class="px-4 py-2 text-gray-700">{{ $product->name }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $product->sku ?? '-' }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $product->barcode ?? '-' }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $product->unit_price }} {{ $product->currency_code }}</td>
                                    <td class="px-4 py-2 text-gray-700">%{{ $product->vat_rate }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $product->stock_quantity }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">Kayitli urun yok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
