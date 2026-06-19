<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class SchoolClass extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = ['tenant_id', 'name', 'year_level_id', 'created_by_user_id'];

    public function yearLevel(): BelongsTo
    {
        return $this->belongsTo(YearLevel::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_users', 'class_id', 'user_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'class_students', 'class_id', 'student_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(StudentNote::class, 'class_id');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', "%{$term}%");
    }

    public function scopeAssignedTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
    }
}
