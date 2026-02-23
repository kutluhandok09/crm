<?php

namespace App\Models\TenantApp;

use Illuminate\Database\Eloquent\Relations\MorphMany;

class CashAccount extends TenantModel
{
    protected $fillable = [
        'name',
        'currency_code',
        'opening_balance',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:4',
    ];

    public function outgoingTransactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'source');
    }
}
