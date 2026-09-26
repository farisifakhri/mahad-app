<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\ParentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageParents extends ManageRecords
{
    protected static string $resource = ParentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
