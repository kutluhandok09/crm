<?php

namespace App\Models\TenantApp;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends TenantModel
{
    protected $fillable = [
        'invoice_no',
        'type',
        'customer_id',
        'issue_date',
        'due_date',
        'currency_code',
        'exchange_rate',
        'subtotal',
        'vat_total',
        'grand_total',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'subtotal' => 'decimal:4',
        'vat_total' => 'decimal:4',
        'grand_total' => 'decimal:4',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
