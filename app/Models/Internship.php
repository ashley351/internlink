<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Internship extends Model
{
    protected $fillable = [
        'organisation_id', 'title', 'field', 'description', 'location',
        'eligible_courses', 'slots_total', 'duration_weeks', 'closes_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'eligible_courses' => 'array',
            'closes_at' => 'date',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organisation_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(InternshipApplication::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open')->whereDate('closes_at', '>=', today());
    }

    public function getSlotsLeftAttribute(): int
    {
        return max(0, $this->slots_total - $this->applications()->whereIn('status', ['offered', 'placed'])->count());
    }
}
