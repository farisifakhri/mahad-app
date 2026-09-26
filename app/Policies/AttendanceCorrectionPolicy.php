<?php

namespace App\Policies;

use App\Enums\SubmissionStatusEnum;
use App\Models\AttendanceCorrection;
use App\Models\User;
use App\Repositories\GroupRepository;

class AttendanceCorrectionPolicy
{
    public function view(User $user, AttendanceCorrection $correction): bool
    {
        return $user->can('reports.view') && app(GroupRepository::class)->canAccess($user, $correction->period->group_id);
    }

    public function review(User $user, AttendanceCorrection $correction): bool
    {
        return $user->hasRole('murabbi') && $user->can('corrections.review') && $correction->status === SubmissionStatusEnum::PENDING && app(GroupRepository::class)->canAccess($user, $correction->period->group_id);
    }
}
