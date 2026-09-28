<?php

namespace App\Filament\Pages;

use App\Actions\FinalizeWeeklyReport;
use App\Actions\RequestAttendanceCorrection;
use App\Actions\ReviewAttendanceCorrection;
use App\Models\Attendance;
use App\Services\MonitoringService;
use App\Services\OperationalQuery;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

class WeeklyReports extends Page
{
    protected string $view = 'filament.pages.weekly-reports';

    protected static ?string $title = 'Laporan Mingguan';

    public string $groupId = '';

    public string $periodDate = '';

    #[Locked]
    public ?string $correctionAttendanceId = null;

    #[Locked]
    public int $correctionVersion = 0;

    public string $newStatus = 'HADIR';

    public string $newNotes = '';

    public string $correctionReason = '';

    public array $reviewNotes = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reports.view') ?? false;
    }

    public function mount(): void
    {
        $this->periodDate = now()->subWeek()->toDateString();
    }

    public function finalize(): void
    {
        app(FinalizeWeeklyReport::class)->execute(auth()->user(), $this->groupId, $this->periodDate);
        Notification::make()->title('Laporan final versi 1 diterbitkan')->success()->send();
    }

    public function beginCorrection(string $attendanceId, int $version): void
    {
        $record = Attendance::findOrFail($attendanceId);
        Gate::authorize('correct', $record);
        $this->correctionAttendanceId = $record->id;
        $this->correctionVersion = $version;
        $this->newStatus = $record->status->value;
        $this->newNotes = $record->notes ?? '';
    }

    public function requestCorrection(): void
    {
        app(RequestAttendanceCorrection::class)->execute(auth()->user(), Attendance::findOrFail($this->correctionAttendanceId),
            ['new_status' => $this->newStatus, 'new_notes' => $this->newNotes ?: null, 'reason' => $this->correctionReason, 'version' => $this->correctionVersion]);
        $this->correctionAttendanceId = null;
        $this->correctionReason = '';
        Notification::make()->title('Koreksi menunggu review murabbi')->success()->send();
    }

    public function reviewCorrection(int $id, string $decision): void
    {
        $record = app(OperationalQuery::class)->corrections(auth()->user())->findOrFail($id);
        app(ReviewAttendanceCorrection::class)->execute(auth()->user(), $record, $decision, $this->reviewNotes[$id] ?? null);
        Notification::make()->title('Review koreksi tersimpan')->success()->send();
    }

    protected function getViewData(): array
    {
        return ['groups' => app(MonitoringService::class)->groups(auth()->user())->get(),
            'periods' => app(OperationalQuery::class)->periods(auth()->user())->limit(30)->get(),
            'corrections' => app(OperationalQuery::class)->corrections(auth()->user())->limit(50)->get()];
    }
}
