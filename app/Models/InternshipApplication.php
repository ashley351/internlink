<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternshipApplication extends Model
{
    public const STATUSES = ['submitted', 'shortlisted', 'interview', 'offered', 'placed', 'rejected', 'withdrawn'];

    protected $fillable = ['internship_id', 'student_id', 'cover_letter', 'status', 'interview_at'];

    protected function casts(): array
    {
        return ['interview_at' => 'datetime'];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
