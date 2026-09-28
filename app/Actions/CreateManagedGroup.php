<?php

namespace App\Actions;

use App\Models\Group;
use App\Models\Mabna;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateManagedGroup
{
    public function execute(User $actor, string $sourceGroupId, array $data): Group
    {
        $data = Validator::make($data, ['name' => ['required', 'string', 'max:255'], 'academic_year' => ['required', 'regex:/^\\d{4}\\/\\d{4}$/']])->validate();
        $source = Group::findOrFail($sourceGroupId);
        Gate::forUser($actor)->authorize('organizeGroup', $source);

        return DB::transaction(function () use ($actor, $source, $data) {
            Mabna::whereKey($source->mabna_id)->lockForUpdate()->firstOrFail();
            $source = Group::whereKey($source->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('organizeGroup', $source);
            if (Group::withTrashed()->where('mabna_id', $source->mabna_id)->where('academic_year', $data['academic_year'])->where('name', $data['name'])->exists()) {
                throw ValidationException::withMessages(['name' => 'Nama kelompok sudah digunakan pada mabna dan tahun akademik ini.']);
            }
            $group = Group::create($data + ['mabna_id' => $source->mabna_id, 'murabbi_id' => $source->murabbi_id]);
            if ($actor->hasAnyRole(['mudabbir'])) {
                $group->mudabbirs()->attach($actor->id);
            }
            activity('group-assignment')->causedBy($actor)->performedOn($group)->withProperties(['source_group_id' => $source->id])->log('Kelompok dibuat untuk pembagian mahasantri');

            return $group;
        });
    }
}
