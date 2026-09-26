<?php

namespace App\Services;

use App\Models\ActivitySession;
use App\Models\AttendanceCorrection;
use App\Models\User;
use App\Models\WeeklyPeriod;
use App\Repositories\GroupRepository;
use Illuminate\Database\Eloquent\Builder;

class OperationalQuery
{
    public function __construct(private GroupRepository $groups) {}

    public function sessions(User $user): Builder
    {
        return ActivitySession::whereIn('group_id', $this->groups->visibleTo($user)->select('groups.id'))->with(['group', 'activity'])->latest('date')->orderByDesc('starts_at');
    }

    public function periods(User $user): Builder
    {
        return WeeklyPeriod::whereIn('group_id', $this->groups->visibleTo($user)->select('groups.id'))->with(['group', 'reports'])->latest('starts_on');
    }

    public function corrections(User $user): Builder
    {
        return AttendanceCorrection::whereHas('period', fn (Builder $query) => $query->whereIn('group_id', $this->groups->visibleTo($user)->select('groups.id')))
            ->with(['period.group', 'attendance.student.user', 'requestedBy', 'reviewedBy'])->latest();
    }
}
