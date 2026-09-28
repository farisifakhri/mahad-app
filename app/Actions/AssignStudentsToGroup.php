<?php

namespace App\Actions;

use App\Models\Group;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AssignStudentsToGroup
{
    public function execute(User $actor, string $targetId, array $rows): int
    {
        $rows = Validator::make(['rows' => $rows], [
            'rows' => ['required', 'array', 'min:1', 'max:500'],
            'rows.*.student_id' => ['required', 'uuid', 'distinct'],
            'rows.*.from_group_id' => ['required', 'uuid'],
        ])->validate()['rows'];
        $target = Group::findOrFail($targetId);
        Gate::forUser($actor)->authorize('organizeGroup', $target);

        return DB::transaction(function () use ($actor, $targetId, $rows) {
            $ids = array_unique(array_merge([$targetId], array_column($rows, 'from_group_id')));
            // Opening a session locks its group too, so roster creation cannot interleave with transfers.
            $groups = Group::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $target = $groups->get($targetId);
            if (! $target) {
                throw ValidationException::withMessages(['target' => 'Kelompok tujuan tidak aktif.']);
            }
            Gate::forUser($actor)->authorize('organizeGroup', $target);
            $students = Student::whereIn('id', array_column($rows, 'student_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $moved = 0;
            foreach ($rows as $row) {
                $student = $students->get($row['student_id']);
                if (! $student || $student->group_id !== $row['from_group_id']) {
                    throw ValidationException::withMessages(['students' => 'Keanggotaan berubah. Muat ulang daftar sebelum membagi kelompok.']);
                }
                Gate::forUser($actor)->authorize('assignStudentGroup', [$student, $target]);
                if ($student->group_id === $target->id) {
                    continue;
                }
                $oldId = $student->group_id;
                $student->update(['group_id' => $target->id]);
                activity('group-assignment')->causedBy($actor)->performedOn($student)->withProperties(['old' => ['group_id' => $oldId], 'attributes' => ['group_id' => $target->id]])->log('Mahasantri dipindahkan kelompok');
                $moved++;
            }

            return $moved;
        });
    }
}
