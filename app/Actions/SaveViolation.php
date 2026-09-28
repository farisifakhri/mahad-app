<?php

namespace App\Actions;

use App\Models\Student;
use App\Models\User;
use App\Models\Violation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaveViolation
{
    public function execute(User $actor, array $data, ?Violation $record = null, ?UploadedFile $photo = null): Violation
    {
        $data = Validator::make($data + ['photo' => $photo], [
            'student_id' => ['required', 'uuid', 'exists:students,id'], 'violation_category_id' => ['required', 'integer', 'exists:violation_categories,id'],
            'occurred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'description' => ['required', 'string', 'min:5', 'max:2000'],
            'version' => ['required', 'integer', 'min:0'], 'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ])->validate();
        $student = Student::findOrFail($data['student_id']);
        Gate::forUser($actor)->authorize($record ? 'update' : 'create', $record ? $record : [Violation::class, $student]);
        if ($record && $record->student_id !== $student->id) {
            throw ValidationException::withMessages(['student_id' => 'Mahasantri pada pelanggaran tidak dapat diganti.']);
        }

        return DB::transaction(function () use ($actor, $record, $data, $student, $photo) {
            if ($record) {
                $record = Violation::whereKey($record->id)->lockForUpdate()->firstOrFail();
                Gate::forUser($actor)->authorize('update', $record);
                if ($record->version !== (int) $data['version']) {
                    throw ValidationException::withMessages(['violation' => 'Pelanggaran telah berubah. Muat ulang.']);
                }
            } else {
                if ((int) $data['version'] !== 0) {
                    throw ValidationException::withMessages(['version' => 'Versi tidak valid.']);
                }
                Gate::forUser($actor)->authorize('create', [Violation::class, $student]);
            }
            $attributes = ['student_id' => $student->id, 'violation_category_id' => $data['violation_category_id'], 'occurred_on' => $data['occurred_on'], 'description' => $data['description'], 'version' => ($record?->version ?? 0) + 1];
            if ($record) {
                $record->update($attributes);
            } else {
                $record = Violation::create($attributes + ['recorded_by' => $actor->id]);
            }
            if ($photo) {
                $record->addMedia($photo)->toMediaCollection('photos');
            }

            return $record;
        });
    }
}
