<?php

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Models\Activity;
use App\Models\Group;
use App\Models\Mabna;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\User;
use App\Models\ViolationCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SipmaSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('Sipma123!');
        $createUser = fn (string $name, string $email, UserRoleEnum $role) => User::firstOrCreate(['email' => $email], [
            'name' => $name, 'email' => $email, 'password' => $password,
            'role' => $role, 'email_verified_at' => now(),
        ]);

        $mabna = Mabna::firstOrCreate(['name' => 'Mabna Syekh Nawawi'], ['gender' => 'male', 'status' => true]);
        $createUser('Super Admin SIPMA', 'admin@sipma.test', UserRoleEnum::SUPER_ADMIN);
        $murabbi = $createUser('Murabbi SIPMA', 'murabbi@sipma.test', UserRoleEnum::MURABBI);

        for ($groupNumber = 1; $groupNumber <= 5; $groupNumber++) {
            $group = Group::withTrashed()->firstOrCreate([
                'mabna_id' => $mabna->id,
                'name' => 'Kelompok '.$groupNumber, 'academic_year' => '2026/2027',
            ], ['murabbi_id' => $murabbi->id]);

            for ($i = 1; $i <= 2; $i++) {
                $number = ($groupNumber - 1) * 2 + $i;
                $mudabbir = $createUser('Mudabbir '.$number, 'mudabbir'.$number.'@sipma.test', $number === 1 ? UserRoleEnum::KETUA_MUDABBIR : UserRoleEnum::MUDABBIR);
                $group->mudabbirs()->syncWithoutDetaching([$mudabbir->id]);
            }

            for ($i = 1; $i <= 20; $i++) {
                $number = ($groupNumber - 1) * 20 + $i;
                $user = $createUser(fake('id_ID')->firstNameMale().' '.fake('id_ID')->lastNameMale(), 'mahasantri'.$number.'@sipma.test', UserRoleEnum::MAHASANTRI);
                $student = Student::withTrashed()->firstOrCreate(['user_id' => $user->id], [
                    'user_id' => $user->id, 'group_id' => $group->id,
                    'nim' => '112601'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
                    'faculty' => 'Fakultas Sains dan Teknologi', 'study_program' => 'Sistem Informasi',
                    'phone' => fake('id_ID')->phoneNumber(), 'date_of_birth' => '2007-01-01',
                ]);

                if ($i <= 2) {
                    $parentUser = $createUser('Wali '.$number, 'orangtua'.$number.'@sipma.test', UserRoleEnum::ORANG_TUA);
                    $parent = ParentModel::firstOrCreate(['user_id' => $parentUser->id], ['phone' => fake('id_ID')->phoneNumber(), 'address' => fake('id_ID')->address()]);
                    $parent->students()->syncWithoutDetaching([$student->id => ['relationship' => 'orang tua']]);
                }
            }
        }

        foreach (['TS', 'SS', 'TM', 'SM', 'SI'] as $code) {
            Activity::firstOrCreate(['code' => $code], ['name' => $code, 'description' => 'Master kegiatan '.$code, 'is_active' => true]);
        }

        ViolationCategory::firstOrCreate(['name' => 'Kedisiplinan'], ['description' => 'Kategori awal; sesuaikan dengan peraturan mabna.', 'points' => 1]);
        $this->call(DemoIdentitySeeder::class);
    }
}
