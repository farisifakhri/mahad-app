<?php

use App\Models\Activity;
use App\Models\ActivitySession;
use App\Models\Attendance;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.connections.'.config('database.default').'.database') !== 'sipma_testing') {
    throw new RuntimeException('Visual fixtures may only use sipma_testing.');
}
$app->make(DatabaseSeeder::class)->run();
$leader = User::where('email', 'mudabbir1@sipma.test')->firstOrFail();
Auth::login($leader);
$group = $leader->managedGroups()->firstOrFail();
$activity = Activity::where('code', 'TS')->firstOrFail();
$session = ActivitySession::firstOrCreate(['group_id' => $group->id, 'activity_id' => $activity->id, 'date' => now()->toDateString(), 'occurrence' => 1], ['starts_at' => '05:00', 'ends_at' => '06:00', 'opened_by' => $leader->id, 'opened_at' => now(), 'status' => 'open', 'roster' => $group->students()->with('user')->orderBy('nim')->get()->map(fn ($s) => ['student_id' => $s->id, 'name' => $s->user->name, 'nim' => $s->nim])->all()]);
foreach (array_slice($session->roster, 0, 8) as $member) {
    Attendance::firstOrCreate(['activity_session_id' => $session->id, 'student_id' => $member['student_id']], ['status' => 'HADIR', 'recorded_by' => $leader->id, 'version' => 1]);
}
echo 'Visual fixtures seeded only in sipma_testing; session '.$session->id.PHP_EOL;
