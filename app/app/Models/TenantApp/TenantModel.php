<?php

namespace App\Models\TenantApp;

use Illuminate\Database\Eloquent\Model;

abstract class TenantModel extends Model
{
    protected $connection = 'tenant';
}
