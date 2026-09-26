<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AttendanceStatusEnum: string implements HasLabel
{
    case HADIR = 'HADIR';
    case ALFA = 'ALFA';
    case IZIN = 'IZIN';
    case SAKIT = 'SAKIT';

    public function getLabel(): string
    {
        return match ($this) {
            self::HADIR => 'Hadir',
            self::ALFA => 'Alfa',
            self::IZIN => 'Izin',
            self::SAKIT => 'Sakit',
        };
    }

    public static function options(): array
    {
        return array_column(array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->getLabel()], self::cases()), 'label', 'value');
    }
}
