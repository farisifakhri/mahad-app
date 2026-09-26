<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivitySession extends Model
{
    use HasFactory;

    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = CarbonImmutable::parse($value)->toDateString();
    }

    protected $fillable = ['activity_id', 'group_id', 'date', 'starts_at', 'ends_at', 'opened_by', 'opened_at', 'status', 'roster', 'version', 'occurrence'];

    protected function casts(): array
    {
        return ['date' => 'date', 'opened_at' => 'immutable_datetime', 'roster' => 'array', 'version' => 'integer', 'occurrence' => 'integer'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class)->withTrashed();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function absenceSubmissions(): HasMany
    {
        return $this->hasMany(AbsenceSubmission::class);
    }
}
