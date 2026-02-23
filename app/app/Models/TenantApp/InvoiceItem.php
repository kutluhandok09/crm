<?php

namespace App\Models\TenantApp;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceItem extends TenantModel
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'vat_rate',
        'vat_amount',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:4',
        'line_total' => 'decimal:4',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function serials(): HasMany
    {
        return $this->hasMany(InvoiceItemSerial::class);
    }
}
