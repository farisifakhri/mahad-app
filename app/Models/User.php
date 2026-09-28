<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRoleEnum;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, \Illuminate\Database\Eloquent\Concerns\HasUuids, Notifiable, \Spatie\Permission\Traits\HasRoles;

    protected $attributes = ['role' => 'mahasantri'];

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
    ];

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
            'role' => UserRoleEnum::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->hasAnyRole(['super_admin', 'pengasuh', 'murabbi', 'mudabbir']);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function parentProfile(): HasOne
    {
        return $this->hasOne(ParentModel::class);
    }

    public function supervisedGroups(): HasMany
    {
        return $this->hasMany(Group::class, 'murabbi_id');
    }

    public function managedGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_mudabbir')->withTimestamps();
    }

    public function recordedAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'recorded_by');
    }

    public function recordedViolations(): HasMany
    {
        return $this->hasMany(Violation::class, 'recorded_by');
    }

    public function reviewedSubmissions(): HasMany
    {
        return $this->hasMany(AbsenceSubmission::class, 'reviewed_by');
    }
}
