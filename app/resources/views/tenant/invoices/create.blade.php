<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $defaultType === 'purchase' ? 'Alis Faturasi' : 'Hizli Satis Faturasi' }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow" x-data="invoiceForm()">
                <form method="POST" action="{{ route('tenant.invoices.store', absolute: false) }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <x-input-label for="invoice_no" value="Fatura No (opsiyonel)" />
                            <x-text-input id="invoice_no" name="invoice_no" class="mt-1 block w-full" :value="old('invoice_no')" />
                        </div>
                        <div>
                            <x-input-label for="type" value="Fatura Turu" />
                            <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach (['sale' => 'Satis', 'purchase' => 'Alis', 'sale_return' => 'Satis Iade', 'purchase_return' => 'Alis Iade'] as $type => $label)
                                    <option value="{{ $type }}" @selected(old('type', $defaultType) === $type)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="issue_date" value="Fatura Tarihi" />
                            <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1 block w-full" :value="old('issue_date', now()->toDateString())" required />
                        </div>
                        <div>
                            <x-input-label for="due_date" value="Vade Tarihi" />
                            <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" :value="old('due_date')" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <x-input-label for="customer_id" value="Cari (opsiyonel)" />
                            <select id="customer_id" name="customer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Secilmedi</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="currency_code" value="Para Birimi" />
                            <select id="currency_code" name="currency_code" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach (['TRY', 'GBP', 'EUR', 'USD'] as $currency)
                                    <option value="{{ $currency }}" @selected(old('currency_code', 'TRY') === $currency)>{{ $currency }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="exchange_rate" value="Kur (fatura aninda kayit)" />
                            <x-text-input id="exchange_rate" name="exchange_rate" type="number" step="0.00000001" class="mt-1 block w-full" :value="old('exchange_rate', '1')" required />
                        </div>
                    </div>

                    <div class="rounded-md border border-dashed border-gray-300 p-4">
                        <h3 class="font-semibold text-gray-900">Kamera ile Seri/Barkod Tara</h3>
                        <p class="mt-1 text-sm text-gray-500">Tarama sonucunu asagidaki alana getirir; ilgili satirin seri alanina ekleyebilirsin.</p>
                        <div class="mt-3">
                            <x-text-input id="quick-serial-input" class="block w-full" placeholder="Tarama sonucu burada gorunur" />
                        </div>
                        <div class="mt-3">
                            <x-barcode-scanner target-input="quick-serial-input" title="Kamera Ac" />
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900">Kalemler</h3>
                            <button type="button" @click="addItem()" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Kalem Ekle
                            </button>
                        </div>

                        <template x-for="(item, index) in items" :key="item.key">
                            <div class="rounded-md border border-gray-200 p-4">
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700">Urun</label>
                                        <select :name="`items[${index}][product_id]`" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                            <option value="">Seciniz</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->barcode ?: '-' }})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Miktar</label>
                                        <input :name="`items[${index}][quantity]`" type="number" step="0.001" min="0.001" value="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Birim Fiyat</label>
                                        <input :name="`items[${index}][unit_price]`" type="number" step="0.0001" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">KDV %</label>
                                        <input :name="`items[${index}][vat_rate]`" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>

                                    <div class="flex items-end">
                                        <button type="button" @click="removeItem(index)" class="inline-flex w-full justify-center rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 hover:bg-red-100">
                                            Sil
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="block text-sm font-medium text-gray-700">Seri Numaralari (virgulle ayir)</label>
                                    <div class="mt-1 flex gap-2">
                                        <input :id="`serial-input-${item.key}`" :name="`items[${index}][serial_numbers_raw]`" type="text" placeholder="SN001,SN002" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        <button type="button"
                                            @click="appendScanned(item.key)"
                                            class="inline-flex shrink-0 items-center rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm text-indigo-700 hover:bg-indigo-100">
                                            Tara -> Ekle
                                        </button>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Seri takipli urunlerde miktar kadar seri girmek zorunludur.</p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div>
                        <x-input-label for="notes" value="Not" />
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                    </div>

                    <div>
                        <x-primary-button>Faturayi Kaydet ve Isle</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function invoiceForm() {
            return {
                items: [{ key: Date.now() }],
                addItem() {
                    this.items.push({ key: Date.now() + Math.floor(Math.random() * 1000) });
                },
                removeItem(index) {
                    if (this.items.length === 1) {
                        return;
                    }
                    this.items.splice(index, 1);
                },
                appendScanned(key) {
                    const scannedInput = document.getElementById('quick-serial-input');
                    const target = document.getElementById(`serial-input-${key}`);
                    if (!scannedInput || !target || !scannedInput.value) {
                        return;
                    }

                    const value = scannedInput.value.trim();
                    if (!value) {
                        return;
                    }

                    target.value = target.value ? `${target.value},${value}` : value;
                    scannedInput.value = '';
                }
            };
        }
    </script>
</x-app-layout>
