<?php

namespace App\Models;

use App\Enums\AttendanceStatusEnum;
use App\Enums\SubmissionStatusEnum;
use App\Traits\AuditsChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;

class AbsenceSubmission extends Model implements HasMedia
{
    use AuditsChanges, HasFactory, \Spatie\MediaLibrary\InteractsWithMedia;

    protected $fillable = ['student_id', 'activity_session_id', 'type', 'status', 'reason', 'latitude', 'longitude', 'reviewed_by', 'reviewed_at', 'review_notes', 'version'];

    protected function casts(): array
    {
        return ['version' => 'integer', 'type' => AttendanceStatusEnum::class, 'status' => SubmissionStatusEnum::class, 'latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'reviewed_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function activitySession(): BelongsTo
    {
        return $this->belongsTo(ActivitySession::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('evidence')->useDisk('local')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
    }
}
