<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Yeni Urun
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow">
                <form method="POST" action="{{ route('tenant.products.store') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @csrf
                    <div class="md:col-span-2">
                        <x-input-label for="name" value="Urun Adi" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="sku" value="SKU" />
                        <x-text-input id="sku" name="sku" class="mt-1 block w-full" :value="old('sku')" />
                        <x-input-error class="mt-2" :messages="$errors->get('sku')" />
                    </div>

                    <div>
                        <x-input-label for="barcode" value="Barkod" />
                        <x-text-input id="barcode" name="barcode" class="mt-1 block w-full" :value="old('barcode')" />
                        <x-input-error class="mt-2" :messages="$errors->get('barcode')" />
                    </div>

                    <div class="md:col-span-2">
                        <x-barcode-scanner target-input="barcode" title="Barkod Tara (Kamera)" />
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
                        <x-input-label for="unit_price" value="Birim Fiyat" />
                        <x-text-input id="unit_price" name="unit_price" type="number" step="0.0001" class="mt-1 block w-full" :value="old('unit_price', '0')" required />
                    </div>

                    <div>
                        <x-input-label for="vat_rate" value="KDV (%)" />
                        <x-text-input id="vat_rate" name="vat_rate" type="number" step="0.01" class="mt-1 block w-full" :value="old('vat_rate', '20')" required />
                    </div>

                    <div>
                        <x-input-label for="stock_quantity" value="Baslangic Stok" />
                        <x-text-input id="stock_quantity" name="stock_quantity" type="number" step="0.001" class="mt-1 block w-full" :value="old('stock_quantity', '0')" required />
                    </div>

                    <div class="md:col-span-2">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="track_serials" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('track_serials', true)) />
                            <span class="text-sm text-gray-700">Seri numarasi takibi zorunlu</span>
                        </label>
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="description" value="Aciklama" />
                        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                    </div>

                    <div class="md:col-span-2 flex items-center gap-3">
                        <x-primary-button>Kaydet</x-primary-button>
                        <a href="{{ route('tenant.products.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Iptal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
