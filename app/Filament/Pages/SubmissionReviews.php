<?php

namespace App\Filament\Pages;

use App\Actions\ReviewAbsenceSubmission;
use App\Services\MonitoringService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SubmissionReviews extends Page
{
    protected string $view = 'filament.pages.submission-reviews';

    protected static ?string $title = 'Review Izin / Sakit';

    public array $reviewNotes = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('submissions.view') ?? false;
    }

    public function review(int $id, string $decision, int $version): void
    {
        $submission = app(MonitoringService::class)->submissions(auth()->user())->findOrFail($id);
        app(ReviewAbsenceSubmission::class)->execute(auth()->user(), $submission, $decision, $this->reviewNotes[$id] ?? null, $version);
        Notification::make()->title('Keputusan pengajuan tersimpan')->success()->send();
    }

    protected function getViewData(): array
    {
        return ['submissions' => app(MonitoringService::class)->submissions(auth()->user())->with('media')->latest()->limit(100)->get()];
    }
}
