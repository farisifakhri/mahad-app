<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyPeriod extends Model
{
    public function setStartsOnAttribute($value): void
    {
        $this->attributes['starts_on'] = CarbonImmutable::parse($value)->toDateString();
    }

    public function setEndsOnAttribute($value): void
    {
        $this->attributes['ends_on'] = CarbonImmutable::parse($value)->toDateString();
    }

    protected $fillable = ['group_id', 'starts_on', 'ends_on', 'finalized_by', 'finalized_at', 'current_version'];

    protected function casts(): array
    {
        return ['starts_on' => 'immutable_date', 'ends_on' => 'immutable_date', 'finalized_at' => 'immutable_datetime', 'current_version' => 'integer'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class)->withTrashed();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(WeeklyReport::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
