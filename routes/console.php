<?php

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sipma:promote-demo-leader', function () {
    if (! app()->environment(['local', 'testing'])) {
        $this->error('Hanya untuk data demo lokal.');

        return 1;
    }
    $user = User::where('email', 'mudabbir1@sipma.test')->first();
    if (! $user || ! $user->managedGroups()->exists()) {
        $this->error('Akun demo belum memiliki penugasan kelompok.');

        return 1;
    }
    $user->update(['role' => UserRoleEnum::KETUA_MUDABBIR]);
    $this->info('mudabbir1@sipma.test sekarang ketua mudabbir; penugasan tetap tersimpan.');

    return 0;
})->purpose('Promote the existing local demo mudabbir without rebuilding data');
