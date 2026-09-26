<?php

namespace App\Models;

use App\Enums\AttendanceStatusEnum;
use App\Enums\SubmissionStatusEnum;
use App\Traits\AuditsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    use AuditsChanges;

    protected $fillable = ['attendance_id', 'weekly_period_id', 'requested_by', 'attendance_version', 'old_status', 'new_status', 'new_notes', 'reason', 'status', 'reviewed_by', 'reviewed_at', 'review_notes', 'weekly_report_id'];

    protected function casts(): array
    {
        return ['status' => SubmissionStatusEnum::class, 'old_status' => AttendanceStatusEnum::class, 'new_status' => AttendanceStatusEnum::class, 'reviewed_at' => 'immutable_datetime', 'attendance_version' => 'integer'];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(WeeklyPeriod::class, 'weekly_period_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(WeeklyReport::class, 'weekly_report_id');
    }
}
