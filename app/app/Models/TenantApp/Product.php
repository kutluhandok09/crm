<?php

namespace App\Models\TenantApp;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends TenantModel
{
    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'description',
        'currency_code',
        'unit_price',
        'vat_rate',
        'track_serials',
        'stock_quantity',
    ];

    protected $casts = [
        'track_serials' => 'boolean',
        'unit_price' => 'decimal:4',
        'vat_rate' => 'decimal:2',
        'stock_quantity' => 'decimal:3',
    ];

    public function serials(): HasMany
    {
        return $this->hasMany(ProductSerial::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
