<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantApp\Customer;
use App\Models\TenantApp\Invoice;
use App\Models\TenantApp\Product;
use App\Services\TenantApp\InvoicePostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoicePostingService $invoicePostingService,
    ) {
    }

    public function index(): View
    {
        return view('tenant.invoices.index', [
            'invoices' => Invoice::query()
                ->with('customer')
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        return view('tenant.invoices.create', [
            'products' => Product::query()->orderBy('name')->get(),
            'customers' => Customer::query()->orderBy('name')->get(),
            'defaultType' => $request->string('type')->toString() ?: 'sale',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $normalizedItems = collect((array) $request->input('items', []))
            ->map(function (array $item): array {
                $serialNumbers = collect(explode(',', (string) ($item['serial_numbers_raw'] ?? '')))
                    ->map(fn (string $serial): string => trim($serial))
                    ->filter()
                    ->values()
                    ->all();

                $item['serial_numbers'] = $serialNumbers;
                unset($item['serial_numbers_raw']);

                return $item;
            })
            ->values()
            ->all();

        $request->merge([
            'items' => $normalizedItems,
        ]);

        $validated = $request->validate([
            'invoice_no' => ['nullable', 'string', 'max:255', 'unique:tenant.invoices,invoice_no'],
            'type' => ['required', 'in:sale,purchase,sale_return,purchase_return'],
            'customer_id' => ['nullable', 'integer', 'exists:tenant.customers,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency_code' => ['required', 'in:TRY,GBP,EUR,USD'],
            'exchange_rate' => ['required', 'numeric', 'min:0.000001'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:tenant.products,id'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.serial_numbers' => ['required', 'array'],
            'items.*.serial_numbers.*' => ['string', 'max:255'],
        ]);

        $invoiceNo = $validated['invoice_no']
            ?? strtoupper($validated['type']).'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));

        $invoice = $this->invoicePostingService->createAndPost([
            ...$validated,
            'invoice_no' => $invoiceNo,
        ], $request->user()?->getKey());

        return redirect()
            ->route('tenant.invoices.show', $invoice)
            ->with('status', 'invoice-posted');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['customer', 'items.product', 'items.serials.productSerial', 'transactions']);

        return view('tenant.invoices.show', [
            'invoice' => $invoice,
        ]);
    }
}
