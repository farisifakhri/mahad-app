<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStudents extends ManageRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
