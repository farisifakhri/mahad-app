<?php

namespace App\Services;

use App\Models\AbsenceSubmission;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Violation;
use App\Repositories\GroupRepository;
use App\Repositories\StudentRepository;
use Illuminate\Database\Eloquent\Builder;

class MonitoringService
{
    public function __construct(private GroupRepository $groups, private StudentRepository $students) {}

    public function groups(User $user): Builder
    {
        return $this->groups->visibleTo($user);
    }

    public function students(User $user): Builder
    {
        return $this->students->visibleTo($user);
    }

    public function attendances(User $user): Builder
    {
        if ($user->hasAnyRole(['super_admin', 'pengasuh', 'murabbi', 'mudabbir'])) {
            return Attendance::query()->whereHas('activitySession', fn (Builder $sessions) => $sessions->whereIn('group_id', $this->groups($user)->select('groups.id')))->with(['student.user', 'activitySession.activity']);
        }

        return Attendance::query()->whereIn('student_id', $this->students($user)->select('students.id'))
            ->with(['student.user', 'activitySession.activity']);
    }

    public function submissions(User $user): Builder
    {
        if ($user->hasAnyRole(['super_admin', 'mudabbir'])) {
            return AbsenceSubmission::query()->whereHas('activitySession', fn (Builder $sessions) => $sessions->whereIn('group_id', $this->groups($user)->select('groups.id')))->with(['student.user', 'activitySession.activity']);
        }

        return AbsenceSubmission::query()->whereIn('student_id', $this->students($user)->select('students.id'))
            ->with(['student.user', 'activitySession.activity']);
    }

    public function violations(User $user): Builder
    {
        return Violation::query()->whereIn('student_id', $this->students($user)->select('students.id'))->with('category');
    }

    public function statistics(User $user): array
    {
        return [
            'groups' => $this->groups($user)->count(),
            'students' => $this->students($user)->count(),
            'attendances' => $this->attendances($user)->count(),
            'violations' => $this->violations($user)->count(),
        ];
    }
}
