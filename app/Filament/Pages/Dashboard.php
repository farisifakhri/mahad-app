<?php

namespace App\Filament\Pages;

use App\Services\DashboardService;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Beranda Pembinaan';

    protected function getViewData(): array
    {
        return app(DashboardService::class)->overview(auth()->user());
    }
}
