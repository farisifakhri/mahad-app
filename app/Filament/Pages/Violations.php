<?php

namespace App\Filament\Pages;

use App\Actions\SaveViolation;
use App\Models\Violation;
use App\Models\ViolationCategory;
use App\Services\MonitoringService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;

class Violations extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.violations';

    protected static ?string $title = 'Pelanggaran';

    public string $studentId = '';

    public string $categoryId = '';

    public string $occurredOn = '';

    public string $description = '';

    public $photo;

    #[Locked]
    public ?string $recordId = null;

    #[Locked]
    public int $version = 0;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('violations.view') ?? false;
    }

    public function mount(): void
    {
        $this->occurredOn = now()->toDateString();
    }

    public function edit(string $id): void
    {
        $record = app(MonitoringService::class)->violations(auth()->user())->findOrFail($id);
        Gate::authorize('update', $record);
        $this->recordId = $record->id;
        $this->version = $record->version;
        $this->studentId = $record->student_id;
        $this->categoryId = (string) $record->violation_category_id;
        $this->occurredOn = $record->occurred_on->toDateString();
        $this->description = $record->description;
    }

    public function save(): void
    {
        app(SaveViolation::class)->execute(auth()->user(), ['student_id' => $this->studentId, 'violation_category_id' => $this->categoryId,
            'occurred_on' => $this->occurredOn, 'description' => $this->description, 'version' => $this->version],
            $this->recordId ? Violation::findOrFail($this->recordId) : null, $this->photo);
        $this->reset(['recordId', 'version', 'description', 'photo']);
        Notification::make()->title('Pelanggaran tersimpan')->success()->send();
    }

    public function remove(string $id): void
    {
        DB::transaction(function () use ($id) {
            $record = app(MonitoringService::class)->violations(auth()->user())->whereKey($id)->lockForUpdate()->firstOrFail();
            Gate::authorize('delete', $record);
            $record->delete();
        });
        Notification::make()->title('Pelanggaran diarsipkan')->success()->send();
    }

    protected function getViewData(): array
    {
        return ['students' => app(MonitoringService::class)->students(auth()->user())->with('user')->get(),
            'categories' => ViolationCategory::orderBy('name')->get(),
            'violations' => app(MonitoringService::class)->violations(auth()->user())->with(['student.user', 'media'])->latest()->limit(100)->get()];
    }
}
