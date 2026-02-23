<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Faturalar
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.invoices.create', ['type' => 'sale']) }}" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-500">
                    Hizli Satis
                </a>
                <a href="{{ route('tenant.invoices.create', ['type' => 'purchase']) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">
                    Alis Faturasi
                </a>
            </div>
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
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">No</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Tur</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Cari</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Tarih</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Doviz</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Toplam</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Durum</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Islem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($invoices as $invoice)
                                <tr>
                                    <td class="px-4 py-2 text-gray-700">{{ $invoice->invoice_no }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ strtoupper($invoice->type) }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $invoice->customer?->name ?? '-' }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $invoice->issue_date?->format('Y-m-d') }}</td>
                                    <td class="px-4 py-2 text-gray-700">
                                        {{ $invoice->currency_code }} ({{ $invoice->exchange_rate }})
                                    </td>
                                    <td class="px-4 py-2 text-gray-700">{{ $invoice->grand_total }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ strtoupper($invoice->status) }}</td>
                                    <td class="px-4 py-2">
                                        <a href="{{ route('tenant.invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-500">
                                            Detay
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-500">Kayitli fatura yok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $invoices->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
