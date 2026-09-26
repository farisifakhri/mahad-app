<?php

namespace App\Models;

use App\Enums\AttendanceStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use \App\Traits\AuditsChanges, HasFactory, \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $fillable = ['activity_session_id', 'student_id', 'status', 'notes', 'recorded_by'];

    protected function casts(): array
    {
        return ['status' => AttendanceStatusEnum::class];
    }

    public function activitySession(): BelongsTo
    {
        return $this->belongsTo(ActivitySession::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
