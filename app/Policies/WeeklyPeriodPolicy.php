<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WeeklyPeriod;
use App\Repositories\GroupRepository;
use App\Services\WeeklyCalendar;

class WeeklyPeriodPolicy
{
    public function view(User $user, WeeklyPeriod $period): bool
    {
        return $user->can('reports.view') && app(GroupRepository::class)->canAccess($user, $period->group_id);
    }

    public function finalize(User $user, WeeklyPeriod $period): bool
    {
        return $this->view($user, $period) && $user->can('reports.finalize') && $period->finalized_at === null && app(WeeklyCalendar::class)->canFinalize($period->starts_on);
    }
}
