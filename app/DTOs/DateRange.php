<?php

namespace App\DTOs;

use App\Services\WeeklyCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DateRange
{
    public function __construct(public readonly string $from, public readonly string $to) {}

    public static function fromArray(array $input): self
    {
        $calendar = app(WeeklyCalendar::class);
        $data = Validator::make(['from' => $input['from'] ?? $calendar->start(now())->toDateString(), 'to' => $input['to'] ?? $calendar->end(now())->toDateString()],
            ['from' => ['required', 'date_format:Y-m-d'], 'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from']])->validate();
        if (CarbonImmutable::parse($data['from'])->diffInDays(CarbonImmutable::parse($data['to'])) > 366) {
            throw ValidationException::withMessages(['to' => 'Pilih rentang paling lama 366 hari.']);
        }

        return new self($data['from'], $data['to']);
    }
}
