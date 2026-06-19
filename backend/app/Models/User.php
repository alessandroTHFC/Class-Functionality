<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, BelongsToTenant, HasRoles, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function assignedClasses(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_users');
    }

    public function createdClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'created_by_user_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(StudentNote::class);
    }
}
