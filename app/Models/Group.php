<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\Concerns\HasUuids, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = ['mabna_id', 'murabbi_id', 'name', 'academic_year'];

    public function mabna(): BelongsTo
    {
        return $this->belongsTo(Mabna::class);
    }

    public function murabbi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'murabbi_id');
    }

    public function mudabbirs(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_mudabbir')->withTimestamps();
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function activitySessions(): HasMany
    {
        return $this->hasMany(ActivitySession::class);
    }
}
