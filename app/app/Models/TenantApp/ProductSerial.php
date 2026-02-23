<?php

namespace App\Models\TenantApp;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSerial extends TenantModel
{
    protected $fillable = [
        'product_id',
        'serial_number',
        'status',
        'purchase_invoice_item_id',
        'sale_invoice_item_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function invoiceItemSerials(): HasMany
    {
        return $this->hasMany(InvoiceItemSerial::class);
    }
}
