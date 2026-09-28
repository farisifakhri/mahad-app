<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class WeeklyCalendar
{
    public function start(string|CarbonInterface $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, config('sipma.timezone'))->startOfWeek(CarbonInterface::SUNDAY)->startOfDay();
    }

    public function end(string|CarbonInterface $date): CarbonImmutable
    {
        return $this->start($date)->addDays(6);
    }

    public function cutoff(string|CarbonInterface $date): CarbonImmutable
    {
        return $this->end($date)->setTimeFromTimeString(config('sipma.attendance_lock_time').':00');
    }

    public function isLocked(string|CarbonInterface $date): bool
    {
        return CarbonImmutable::now(config('sipma.timezone'))->greaterThanOrEqualTo($this->cutoff($date));
    }

    public function canFinalize(string|CarbonInterface $date): bool
    {
        return CarbonImmutable::now(config('sipma.timezone'))->greaterThanOrEqualTo($this->end($date)->addDay());
    }
}
