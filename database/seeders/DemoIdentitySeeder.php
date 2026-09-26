<?php

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoIdentitySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $names = [
                'murabbi@sipma.test' => ["Muhammad Ara'af, S.Ag", UserRoleEnum::MURABBI],
                'murabbi2@sipma.test' => ['Syahrul Ramdhani, S.Ag', UserRoleEnum::MURABBI],
            ];
            $mudabbirs = [
                'Riyan Hidayat', 'Syarif Hidayatullah', 'Dandi Muchammad Mudzakir',
                'Muhammad Haqi An-Nazili', 'Muhammad Najwan Cahyadi', 'Ahmad Naufal Farhan',
            ];
            for ($number = 1; $number <= 10; $number++) {
                $names['mudabbir'.$number.'@sipma.test'] = [
                    $mudabbirs[$number - 1] ?? 'Mudabbir '.$number.' (belum dikonfirmasi)',
                    $number === 1 ? UserRoleEnum::KETUA_MUDABBIR : UserRoleEnum::MUDABBIR,
                ];
            }
            foreach ($names as $email => [$name, $role]) {
                $user = User::firstOrCreate(['email' => $email], [
                    'name' => $name, 'role' => $role, 'password' => Hash::make('Sipma123!'),
                    'email_verified_at' => now(),
                ]);
                if ($user->name !== $name) {
                    $user->update(['name' => $name]);
                }
            }
            // Only strip Faker academic suffixes from the known demo student accounts.
            foreach (User::where('role', UserRoleEnum::MAHASANTRI)->where('email', 'like', 'mahasantri%@sipma.test')->get() as $user) {
                if (! preg_match('/^mahasantri\d+@sipma\.test$/', $user->email)) {
                    continue;
                }
                $name = trim(preg_replace('/(?:,\s*|\s+)(?:S|M)\.[A-Za-z.]+$/u', '', $user->name));
                if ($name !== $user->name) {
                    $user->update(['name' => $name]);
                }
            }
        });
    }
}
