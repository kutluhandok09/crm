<?php

namespace App\Models\TenantApp;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends TenantModel
{
    protected $fillable = [
        'txn_no',
        'txn_date',
        'type',
        'currency_code',
        'exchange_rate',
        'amount',
        'source_type',
        'source_id',
        'destination_type',
        'destination_id',
        'invoice_id',
        'cheque_id',
        'description',
    ];

    protected $casts = [
        'txn_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'amount' => 'decimal:4',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function cheque(): BelongsTo
    {
        return $this->belongsTo(Cheque::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function destination(): MorphTo
    {
        return $this->morphTo();
    }
}
