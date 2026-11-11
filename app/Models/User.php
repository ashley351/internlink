<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    public const ROLE_STUDENT = 'student';

    public const ROLE_SUPERVISOR = 'supervisor';

    public const ROLE_ORGANISATION = 'organisation';

    public const ROLE_ADMIN = 'admin';

    public const ROLES = [
        self::ROLE_STUDENT,
        self::ROLE_SUPERVISOR,
        self::ROLE_ORGANISATION,
        self::ROLE_ADMIN,
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'course',
        'university',
    ];

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /** @param  array<int, string>  $roles */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class, 'organisation_id');
    }

    public function internshipApplications(): HasMany
    {
        return $this->hasMany(InternshipApplication::class, 'student_id');
    }

    public function supervisorAssignment(): HasOne
    {
        return $this->hasOne(SupervisorAssignment::class, 'student_id');
    }

    public function supervisedStudents(): HasMany
    {
        return $this->hasMany(SupervisorAssignment::class, 'supervisor_id');
    }

    public function logbookEntries(): HasMany
    {
        return $this->hasMany(LogbookEntry::class, 'student_id');
    }

    public function visitRequests(): HasMany
    {
        return $this->hasMany(VisitRequest::class, 'student_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
