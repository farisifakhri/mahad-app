<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyReport extends Model
{
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Published report versions are immutable.'));
        static::deleting(fn () => throw new \LogicException('Published report versions are immutable.'));
    }

    protected $fillable = ['weekly_period_id', 'version', 'snapshot', 'created_by', 'reason'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'version' => 'integer'];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(WeeklyPeriod::class, 'weekly_period_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
