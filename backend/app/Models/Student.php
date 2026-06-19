<?php

namespace App\Models;

use App\Enums\NccdCategoryEnum;
use App\Enums\NccdLevelEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Student extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'given_name', 'family_name', 'date_of_birth',
        'year_level_id', 'nccd_level', 'nccd_category',
        'primary_disability', 'primary_disability_level_formalised',
    ];

    protected $appends = ['full_name'];

    protected function casts(): array
    {
        return [
            'date_of_birth'                       => 'date',
            'primary_disability_level_formalised' => 'boolean',
            'nccd_level'                          => NccdLevelEnum::class,
            'nccd_category'                       => NccdCategoryEnum::class,
        ];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->given_name} {$this->family_name}";
    }

    public function yearLevel(): BelongsTo
    {
        return $this->belongsTo(YearLevel::class);
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_students');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(StudentNote::class);
    }
}
