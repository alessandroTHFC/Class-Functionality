<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Database\Models\Domain;

class Tenant extends BaseTenant
{
    use HasFactory;

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }
}
