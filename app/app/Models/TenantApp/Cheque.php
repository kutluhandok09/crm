<?php

namespace App\Models\TenantApp;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cheque extends TenantModel
{
    protected $fillable = [
        'portfolio_no',
        'type',
        'counterparty_name',
        'amount',
        'currency_code',
        'issue_date',
        'due_date',
        'status',
        'bank_account_id',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
