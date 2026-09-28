<?php

namespace App\Services;

use App\Models\ActivitySession;
use App\Models\Student;
use App\Models\User;
use App\Models\WeeklyPeriod;
use App\Repositories\GroupRepository;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class AttendanceWorkflow
{
    public function __construct(private WeeklyCalendar $calendar, private GroupRepository $groups) {}

    public function period(ActivitySession $session): WeeklyPeriod
    {
        return $this->periodFor($session->group_id, $session->date->toDateString());
    }

    public function periodFor(string $groupId, string $date): WeeklyPeriod
    {
        $start = $this->calendar->start($date)->toDateString();
        // The unique key and current locking read serialize simultaneous first writes.
        WeeklyPeriod::query()->insertOrIgnore([
            'group_id' => $groupId, 'starts_on' => $start,
            'ends_on' => $this->calendar->end($date)->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return WeeklyPeriod::where('group_id', $groupId)->where('starts_on', $start)->lockForUpdate()->firstOrFail();
    }

    public function canRecord(User $user, ActivitySession $session): bool
    {
        return $user->hasAnyRole(['mudabbir']) && $user->can('attendances.record')
            && $this->groups->canAccess($user, $session->group_id) && $this->ordinaryWindow($session);
    }

    public function ordinaryWindow(ActivitySession $session): bool
    {
        return $session->status === 'open' && $session->opened_at !== null && $session->ends_at !== null
            && CarbonImmutable::parse($session->date->toDateString().' '.$session->ends_at, config('sipma.timezone'))->lessThanOrEqualTo(CarbonImmutable::now(config('sipma.timezone')))
            && ! $this->calendar->isLocked($session->date)
            && ! WeeklyPeriod::where('group_id', $session->group_id)->whereDate('starts_on', $this->calendar->start($session->date))->whereNotNull('finalized_at')->exists();
    }

    public function assertWindow(ActivitySession $session, WeeklyPeriod $period): void
    {
        if ($period->finalized_at || ! $this->ordinaryWindow($session)) {
            throw ValidationException::withMessages(['session' => 'Sesi belum selesai atau periode terkunci. Absensi dikunci setiap Sabtu pukul 23.59 WIB.']);
        }
    }

    public function rosterContains(ActivitySession $session, string $studentId): bool
    {
        return in_array($studentId, array_column($session->roster ?? [], 'student_id'), true);
    }

    public function assertRoster(ActivitySession $session, string $studentId): Student
    {
        if (! $this->rosterContains($session, $studentId)) {
            throw ValidationException::withMessages(['student_id' => 'Mahasantri tidak tercatat dalam kelompok sesi ini.']);
        }

        return Student::withTrashed()->findOrFail($studentId);
    }

    public function state(User $user, ActivitySession $session): array
    {
        return [
            'can_edit' => $this->canRecord($user, $session),
            'lock_reason' => $this->ordinaryWindow($session) ? ($this->canRecord($user, $session) ? null : 'Anda tidak ditugaskan untuk mencatat sesi ini.') : 'Sesi belum selesai atau periode terkunci.',
            'cutoff' => $this->calendar->cutoff($session->date)->format('d/m/Y H:i').' WIB',
        ];
    }
}
