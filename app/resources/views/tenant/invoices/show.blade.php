<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Fatura: {{ $invoice->invoice_no }}
            </h2>
            <a href="{{ route('tenant.invoices.index', absolute: false) }}" class="text-sm text-gray-600 hover:text-gray-900">Geri</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-lg bg-white p-6 shadow">
                <dl class="grid grid-cols-1 gap-4 md:grid-cols-4 text-sm">
                    <div>
                        <dt class="font-semibold text-gray-700">Tur</dt>
                        <dd class="mt-1 text-gray-900">{{ strtoupper($invoice->type) }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-700">Tarih</dt>
                        <dd class="mt-1 text-gray-900">{{ $invoice->issue_date?->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-700">Doviz/Kur</dt>
                        <dd class="mt-1 text-gray-900">{{ $invoice->currency_code }} / {{ $invoice->exchange_rate }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-700">Cari</dt>
                        <dd class="mt-1 text-gray-900">{{ $invoice->customer?->name ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900">Kalemler</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Urun</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Miktar</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Fiyat</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">KDV</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Toplam</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Seriler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td class="px-4 py-2 text-gray-700">{{ $item->product?->name }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $item->quantity }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $item->unit_price }}</td>
                                    <td class="px-4 py-2 text-gray-700">%{{ $item->vat_rate }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $item->line_total }}</td>
                                    <td class="px-4 py-2 text-gray-700">
                                        @if ($item->serials->isEmpty())
                                            -
                                        @else
                                            {{ $item->serials->pluck('productSerial.serial_number')->filter()->implode(', ') }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900">Finans Ozet</h3>
                <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-3 text-sm">
                    <div>
                        <p class="text-gray-500">Ara Toplam</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $invoice->subtotal }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">KDV Toplami</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $invoice->vat_total }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Genel Toplam</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $invoice->grand_total }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
