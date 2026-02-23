<?php

namespace App\Models\TenantApp;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItemSerial extends TenantModel
{
    protected $fillable = [
        'invoice_item_id',
        'product_serial_id',
    ];

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function productSerial(): BelongsTo
    {
        return $this->belongsTo(ProductSerial::class);
    }
}
