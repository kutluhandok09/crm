<?php

namespace App\Models\TenantApp;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BankAccount extends TenantModel
{
    protected $fillable = [
        'bank_name',
        'account_name',
        'iban',
        'currency_code',
        'opening_balance',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:4',
    ];

    public function cheques(): HasMany
    {
        return $this->hasMany(Cheque::class);
    }

    public function incomingTransactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'destination');
    }
}
