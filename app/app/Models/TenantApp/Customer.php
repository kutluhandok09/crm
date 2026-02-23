<?php

namespace App\Models\TenantApp;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends TenantModel
{
    protected $fillable = [
        'type',
        'name',
        'tax_number',
        'phone',
        'email',
        'address',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
