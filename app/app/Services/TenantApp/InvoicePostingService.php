<?php

namespace App\Services\TenantApp;

use App\Models\TenantApp\Invoice;
use App\Models\TenantApp\InvoiceItem;
use App\Models\TenantApp\InvoiceItemSerial;
use App\Models\TenantApp\Product;
use App\Models\TenantApp\ProductSerial;
use App\Models\TenantApp\Transaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoicePostingService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createAndPost(array $payload, ?int $createdBy = null): Invoice
    {
        return DB::connection('tenant')->transaction(function () use ($payload, $createdBy): Invoice {
            /** @var Invoice $invoice */
            $invoice = Invoice::query()->create([
                'invoice_no' => (string) $payload['invoice_no'],
                'type' => (string) $payload['type'],
                'customer_id' => Arr::get($payload, 'customer_id'),
                'issue_date' => (string) $payload['issue_date'],
                'due_date' => Arr::get($payload, 'due_date'),
                'currency_code' => (string) $payload['currency_code'],
                'exchange_rate' => (float) $payload['exchange_rate'],
                'status' => 'draft',
                'notes' => Arr::get($payload, 'notes'),
                'created_by' => $createdBy,
            ]);

            $totals = [
                'subtotal' => 0.0,
                'vat_total' => 0.0,
                'grand_total' => 0.0,
            ];

            foreach ((array) $payload['items'] as $index => $row) {
                $totals = $this->handleItem($invoice, (int) $index, (array) $row, $totals);
            }

            $invoice->update([
                'subtotal' => $totals['subtotal'],
                'vat_total' => $totals['vat_total'],
                'grand_total' => $totals['grand_total'],
                'status' => 'posted',
            ]);

            Transaction::query()->create([
                'txn_no' => 'TRX-'.$invoice->invoice_no,
                'txn_date' => $invoice->issue_date,
                'type' => $this->resolveTransactionType($invoice->type),
                'currency_code' => $invoice->currency_code,
                'exchange_rate' => $invoice->exchange_rate,
                'amount' => $invoice->grand_total,
                'invoice_id' => $invoice->id,
                'description' => 'Auto-generated from invoice '.$invoice->invoice_no,
            ]);

            return $invoice->fresh(['items.serials.productSerial']);
        });
    }

    /**
     * @param  array<string, mixed>  $itemPayload
     * @param  array<string, float>  $totals
     * @return array<string, float>
     */
    protected function handleItem(Invoice $invoice, int $index, array $itemPayload, array $totals): array
    {
        /** @var Product $product */
        $product = Product::query()->findOrFail($itemPayload['product_id']);
        $quantity = (float) $itemPayload['quantity'];

        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                "items.$index.quantity" => 'Miktar sifirdan buyuk olmali.',
            ]);
        }

        $unitPrice = isset($itemPayload['unit_price'])
            ? (float) $itemPayload['unit_price']
            : (float) $product->unit_price;
        $vatRate = isset($itemPayload['vat_rate'])
            ? (float) $itemPayload['vat_rate']
            : (float) $product->vat_rate;

        $lineSubtotal = round($quantity * $unitPrice, 4);
        $vatAmount = round(($lineSubtotal * $vatRate) / 100, 4);
        $lineTotal = round($lineSubtotal + $vatAmount, 4);

        /** @var InvoiceItem $item */
        $item = $invoice->items()->create([
            'product_id' => $product->id,
            'description' => Arr::get($itemPayload, 'description', $product->name),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'line_total' => $lineTotal,
        ]);

        $serials = collect((array) Arr::get($itemPayload, 'serial_numbers'))
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->map(fn (string $value): string => trim($value))
            ->unique()
            ->values();

        if ($product->track_serials) {
            if ((int) $quantity !== count($serials)) {
                throw ValidationException::withMessages([
                    "items.$index.serial_numbers" => 'Seri takipli urunlerde seri sayisi miktar kadar olmalidir.',
                ]);
            }
        }

        $this->syncSerials($invoice, $item, $product, $serials->all(), $index);
        $this->updateStockByInvoiceType($invoice->type, $product, $quantity, $index);

        $totals['subtotal'] = round($totals['subtotal'] + $lineSubtotal, 4);
        $totals['vat_total'] = round($totals['vat_total'] + $vatAmount, 4);
        $totals['grand_total'] = round($totals['grand_total'] + $lineTotal, 4);

        return $totals;
    }

    /**
     * @param  list<string>  $serialNumbers
     */
    protected function syncSerials(Invoice $invoice, InvoiceItem $item, Product $product, array $serialNumbers, int $index): void
    {
        if (! $product->track_serials) {
            return;
        }

        if (in_array($invoice->type, ['sale', 'purchase_return'], true)) {
            foreach ($serialNumbers as $serialNumber) {
                $serial = ProductSerial::query()
                    ->where('product_id', $product->id)
                    ->where('serial_number', $serialNumber)
                    ->where('status', 'in_stock')
                    ->first();

                if (! $serial) {
                    throw ValidationException::withMessages([
                        "items.$index.serial_numbers" => "Stokta bulunamayan seri numarasi: {$serialNumber}",
                    ]);
                }

                InvoiceItemSerial::query()->create([
                    'invoice_item_id' => $item->id,
                    'product_serial_id' => $serial->id,
                ]);

                $serial->update([
                    'status' => 'sold',
                    'sale_invoice_item_id' => $item->id,
                ]);
            }

            return;
        }

        foreach ($serialNumbers as $serialNumber) {
            $serial = ProductSerial::query()->firstOrNew([
                'product_id' => $product->id,
                'serial_number' => $serialNumber,
            ]);

            $serial->status = 'in_stock';
            $serial->purchase_invoice_item_id = $item->id;
            $serial->save();

            InvoiceItemSerial::query()->firstOrCreate([
                'invoice_item_id' => $item->id,
                'product_serial_id' => $serial->id,
            ]);
        }
    }

    protected function updateStockByInvoiceType(string $invoiceType, Product $product, float $quantity, int $index): void
    {
        $delta = in_array($invoiceType, ['sale', 'purchase_return'], true) ? -$quantity : $quantity;
        $newStock = (float) $product->stock_quantity + $delta;

        if ($newStock < 0) {
            throw ValidationException::withMessages([
                "items.$index.quantity" => "Yetersiz stok: {$product->name}",
            ]);
        }

        $product->update([
            'stock_quantity' => $newStock,
        ]);
    }

    protected function resolveTransactionType(string $invoiceType): string
    {
        return match ($invoiceType) {
            'sale', 'sale_return' => 'cash_in',
            default => 'cash_out',
        };
    }
}
