<?php

namespace App\Filament\Pages;

use App\Actions\OpenActivitySession;
use App\Actions\RecordAttendanceBatch;
use App\Models\Activity;
use App\Services\AttendanceWorkflow;
use App\Services\MonitoringService;
use App\Services\OperationalQuery;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

class OperationalSessions extends Page
{
    protected string $view = 'filament.pages.operational-sessions';

    protected static ?string $title = 'Sesi & Absensi';

    public string $groupId = '';

    public string $activityId = '';

    public string $date = '';

    public string $startsAt = '18:00';

    public string $endsAt = '19:00';

    public int $occurrence = 1;

    #[Locked]
    public ?int $selectedSessionId = null;

    public array $attendanceRows = [];

    public string $filterGroup = '';

    public string $filterActivity = '';

    public string $filterDate = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('sessions.view') ?? false;
    }

    public function mount(): void
    {
        $this->date = now()->toDateString();
        if (request()->filled('session')) {
            $this->selectSession((int) request('session'));
        }
    }

    public function openSession(): void
    {
        $session = app(OpenActivitySession::class)->execute(auth()->user(), ['group_id' => $this->groupId, 'activity_id' => $this->activityId, 'date' => $this->date, 'starts_at' => $this->startsAt, 'ends_at' => $this->endsAt, 'occurrence' => $this->occurrence]);
        $this->selectSession($session->id);
        Notification::make()->title('Sesi dibuka')->success()->send();
    }

    public function selectSession(int $id): void
    {
        $session = app(OperationalQuery::class)->sessions(auth()->user())->findOrFail($id);
        Gate::authorize('view', $session);
        $this->selectedSessionId = $session->id;
        $records = $session->attendances()->get()->keyBy('student_id');
        $this->attendanceRows = array_map(fn ($member) => ['student_id' => $member['student_id'], 'status' => $records->get($member['student_id'])?->status->value ?? '', 'notes' => $records->get($member['student_id'])?->notes ?? '', 'version' => $records->get($member['student_id'])?->version ?? 0], $session->roster ?? []);
    }

    public function saveAttendance(): void
    {
        $session = app(OperationalQuery::class)->sessions(auth()->user())->findOrFail($this->selectedSessionId);
        app(RecordAttendanceBatch::class)->execute(auth()->user(), $session, $this->attendanceRows);
        $this->selectSession($session->id);
        Notification::make()->title('Absensi tersimpan')->success()->send();
        $this->dispatch('attendance-saved');
    }

    protected function getViewData(): array
    {
        $session = $this->selectedSessionId ? app(OperationalQuery::class)->sessions(auth()->user())->findOrFail($this->selectedSessionId) : null;

        return ['groups' => app(MonitoringService::class)->groups(auth()->user())->get(), 'activities' => Activity::where('is_active', true)->get(),
            'sessions' => app(OperationalQuery::class)->sessions(auth()->user())->when($this->filterGroup, fn ($q) => $q->where('group_id', $this->filterGroup))->when($this->filterActivity, fn ($q) => $q->where('activity_id', $this->filterActivity))->when($this->filterDate, fn ($q) => $q->whereDate('date', $this->filterDate))->limit(100)->get(), 'session' => $session,
            'state' => $session ? app(AttendanceWorkflow::class)->state(auth()->user(), $session) : null];
    }
}
