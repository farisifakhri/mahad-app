<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\ViolationCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageViolationCategories extends ManageRecords
{
    protected static string $resource = ViolationCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
