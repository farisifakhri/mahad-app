<?php

namespace App\Policies;

use App\Enums\SubmissionStatusEnum;
use App\Models\AbsenceSubmission;
use App\Models\Student;
use App\Models\User;
use App\Repositories\GroupRepository;
use App\Repositories\StudentRepository;

class AbsenceSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'mudabbir', 'ketua_mudabbir', 'mahasantri']);
    }

    public function view(User $user, AbsenceSubmission $submission): bool
    {
        if ($user->hasAnyRole(['super_admin', 'mudabbir', 'ketua_mudabbir'])) {
            return app(GroupRepository::class)->canAccess($user, $submission->activitySession->group_id);
        }

        return $this->viewAny($user) && app(StudentRepository::class)->canAccess($user, $submission->student_id);
    }

    public function create(User $user, Student $student): bool
    {
        return $user->hasRole('mahasantri') && $student->user_id === $user->id;
    }

    public function review(User $user, AbsenceSubmission $submission): bool
    {
        if ($submission->status !== SubmissionStatusEnum::PENDING) {
            return false;
        }

        return $user->hasRole('super_admin') || ($user->hasAnyRole(['mudabbir', 'ketua_mudabbir'])
            && $user->can('submissions.review') && app(GroupRepository::class)->canAccess($user, $submission->activitySession->group_id));
    }

    public function update(User $user, AbsenceSubmission $submission): bool
    {
        return $this->review($user, $submission);
    }

    public function delete(User $user, AbsenceSubmission $submission): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, AbsenceSubmission $submission): bool
    {
        return false;
    }
}
