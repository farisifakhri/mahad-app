<?php

namespace App\Filament\Resources;

use App\Models\ViolationCategory;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ViolationCategoryResource extends InternalResource
{
    protected static ?string $model = ViolationCategory::class;

    protected static ?string $modelLabel = 'Kategori Pelanggaran';

    protected static ?string $pluralModelLabel = 'Kategori Pelanggaran';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $viewPermission = 'violation_categories.view';

    protected static string $managePermission = 'violation_categories.manage';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->unique(ignoreRecord: true)->maxLength(255),
            Textarea::make('description')->label('Deskripsi'),
            TextInput::make('points')->label('Poin')->numeric()->integer()->minValue(0)->required()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nama')->searchable()->sortable(),
            TextColumn::make('points')->label('Poin')->searchable()->sortable(),
        ])->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageViolationCategories::route('/')];
    }
}
