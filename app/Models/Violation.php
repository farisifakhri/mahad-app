<?php

namespace App\Models;

use App\Traits\AuditsChanges;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;

class Violation extends Model implements HasMedia
{
    use AuditsChanges, HasFactory, \Illuminate\Database\Eloquent\Concerns\HasUuids, \Illuminate\Database\Eloquent\SoftDeletes, \Spatie\MediaLibrary\InteractsWithMedia;

    protected $fillable = ['student_id', 'violation_category_id', 'recorded_by', 'occurred_on', 'description', 'version'];

    public function setOccurredOnAttribute($value): void
    {
        $this->attributes['occurred_on'] = CarbonImmutable::parse($value)->toDateString();
    }

    protected function casts(): array
    {
        return ['version' => 'integer', 'occurred_on' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ViolationCategory::class, 'violation_category_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')->useDisk('local')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
    }
}
