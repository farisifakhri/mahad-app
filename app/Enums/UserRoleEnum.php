<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRoleEnum: string implements HasLabel
{
    case SUPER_ADMIN = 'super_admin';
    case MURABBI = 'murabbi';
    case MUDABBIR = 'mudabbir';
    case KETUA_MUDABBIR = 'ketua_mudabbir';
    case MAHASANTRI = 'mahasantri';
    case ORANG_TUA = 'orang_tua';

    public function getLabel(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::MURABBI => 'Murabbi',
            self::MUDABBIR => 'Mudabbir',
            self::KETUA_MUDABBIR => 'Ketua Mudabbir',
            self::MAHASANTRI => 'Mahasantri',
            self::ORANG_TUA => 'Orang Tua',
        };
    }

    public static function options(): array
    {
        return array_column(array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->getLabel()], self::cases()), 'label', 'value');
    }
}
