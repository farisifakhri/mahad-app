<?php

namespace App\Filament\Resources;

use App\Models\Group;
use App\Services\MonitoringService;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GroupResource extends InternalResource
{
    protected static ?string $model = Group::class;

    protected static ?string $modelLabel = 'Kelompok';

    protected static ?string $pluralModelLabel = 'Kelompok';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $viewPermission = 'groups.view';

    protected static string $managePermission = 'groups.manage';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(255),
            Select::make('mabna_id')->relationship('mabna', 'name')->required(),
            TextInput::make('academic_year')->label('Tahun akademik')->required()->regex('/^\d{4}\/\d{4}$/')->maxLength(9),
            Select::make('murabbi_id')->relationship('murabbi', 'name', fn (Builder $query) => $query->where('role', 'murabbi')
                ->when(! auth()->user()->hasRole('super_admin'), fn (Builder $users) => $users->whereIn('users.id', app(MonitoringService::class)->groups(auth()->user())->select('murabbi_id'))))->searchable()->preload(),
            Select::make('mudabbirs')->relationship('mudabbirs', 'name', fn (Builder $query) => $query->whereIn('role', ['mudabbir'])
                ->when(! auth()->user()->hasRole('super_admin'), fn (Builder $users) => $users->whereHas('managedGroups', fn (Builder $groups) => $groups->whereIn('groups.id', app(MonitoringService::class)->groups(auth()->user())->select('groups.id')))))->multiple()->searchable()->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nama')->searchable()->sortable(),
            TextColumn::make('mabna.name')->label('Mabna')->searchable()->sortable(),
            TextColumn::make('murabbi.name')->label('Murabbi')->searchable()->sortable(),
            TextColumn::make('academic_year')->label('Tahun akademik')->searchable()->sortable(),
        ])->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return app(MonitoringService::class)->groups(auth()->user())->with(['mabna', 'murabbi']);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageGroups::route('/')];
    }
}
