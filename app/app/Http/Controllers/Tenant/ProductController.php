<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantApp\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('tenant.products.index', [
            'products' => Product::query()->latest('id')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('tenant.products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:tenant.products,sku'],
            'barcode' => ['nullable', 'string', 'max:255', 'unique:tenant.products,barcode'],
            'description' => ['nullable', 'string'],
            'currency_code' => ['required', 'in:TRY,GBP,EUR,USD'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'track_serials' => ['nullable', 'boolean'],
            'stock_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        Product::query()->create([
            ...$validated,
            'track_serials' => (bool) ($validated['track_serials'] ?? false),
        ]);

        return redirect()
            ->route('tenant.products.index')
            ->with('status', 'product-created');
    }
}
