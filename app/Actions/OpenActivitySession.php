<?php

namespace App\Actions;

use App\Models\Activity;
use App\Models\ActivitySession;
use App\Models\Group;
use App\Models\User;
use App\Services\AttendanceWorkflow;
use App\Services\WeeklyCalendar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OpenActivitySession
{
    public function execute(User $actor, array $data): ActivitySession
    {
        $data = Validator::make($data, [
            'group_id' => ['required', 'uuid', 'exists:groups,id'], 'activity_id' => ['required', 'integer', 'exists:activities,id'],
            'date' => ['required', 'date_format:Y-m-d'], 'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'occurrence' => ['nullable', 'integer', 'min:1', 'max:100'],
        ])->validate();
        $group = Group::findOrFail($data['group_id']);
        Gate::forUser($actor)->authorize('create', [ActivitySession::class, $group]);

        return DB::transaction(function () use ($actor, $data, $group) {
            // Serialize group/session creation, preserving same-day occurrences.
            $group = Group::whereKey($group->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('create', [ActivitySession::class, $group]);
            $data['occurrence'] ??= (int) ActivitySession::where('group_id', $group->id)->where('activity_id', $data['activity_id'])->whereDate('date', $data['date'])->max('occurrence') + 1;
            if ($data['occurrence'] > 100) {
                throw ValidationException::withMessages(['activity_id' => 'Jumlah sesi kegiatan hari ini sudah mencapai batas.']);
            }
            if (! Activity::whereKey($data['activity_id'])->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['activity_id' => 'Kegiatan tidak aktif.']);
            }
            $period = app(AttendanceWorkflow::class)->periodFor($group->id, $data['date']);
            if ($period->finalized_at || app(WeeklyCalendar::class)->isLocked($data['date'])) {
                throw ValidationException::withMessages(['date' => 'Periode ini sudah terkunci. Sesi susulan tidak dapat dibuat.']);
            }
            if (ActivitySession::where('group_id', $group->id)->where('activity_id', $data['activity_id'])->whereDate('date', $data['date'])->where('occurrence', $data['occurrence'])->exists()) {
                throw ValidationException::withMessages(['occurrence' => 'Sesi dengan urutan ini sudah ada. Gunakan urutan lain untuk kegiatan berulang.']);
            }
            $roster = $group->students()->with('user')->orderBy('nim')->get()->map(fn ($student) => [
                'student_id' => $student->id, 'nim' => $student->nim, 'name' => $student->user->name,
            ])->all();
            if ($roster === []) {
                throw ValidationException::withMessages(['group_id' => 'Kelompok belum memiliki mahasantri.']);
            }

            return ActivitySession::create($data + ['opened_by' => $actor->id, 'opened_at' => now(), 'status' => 'open', 'roster' => $roster]);
        });
    }
}
