<?php

namespace App\Actions;

use App\Models\User;
use App\Models\WeeklyReport;
use App\Repositories\GroupRepository;
use App\Services\AttendanceWorkflow;
use App\Services\WeeklyReportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FinalizeWeeklyReport
{
    public function execute(User $actor, string $groupId, string $date): WeeklyReport
    {
        Validator::make(['date' => $date], ['date' => ['required', 'date_format:Y-m-d']])->validate();
        abort_unless($actor->can('reports.finalize') && app(GroupRepository::class)->canAccess($actor, $groupId), 403);

        return DB::transaction(function () use ($actor, $groupId, $date) {
            $period = app(AttendanceWorkflow::class)->periodFor($groupId, $date);
            Gate::forUser($actor)->authorize('finalize', $period);
            $snapshot = app(WeeklyReportService::class)->snapshot($period);
            if ($snapshot['sessions'] === [] || $snapshot['totals']['BELUM_DIISI'] > 0) {
                throw ValidationException::withMessages(['report' => 'Laporan membutuhkan sesi dan absensi lengkap. BELUM_DIISI tidak dianggap ALFA.']);
            }
            $period->update(['finalized_by' => $actor->id, 'finalized_at' => now()]);

            return app(WeeklyReportService::class)->publish($actor, $period);
        });
    }
}
