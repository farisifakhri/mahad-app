<?php

namespace App\Filament\Widgets;

use App\Services\MonitoringService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GroupStatistics extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $stats = app(MonitoringService::class)->statistics(auth()->user());

        return [
            Stat::make('Kelompok binaan', $stats['groups']),
            Stat::make('Mahasantri', $stats['students']),
            Stat::make('Catatan absensi', $stats['attendances']),
            Stat::make('Pelanggaran', $stats['violations']),
        ];
    }
}
