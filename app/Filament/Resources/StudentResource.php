<?php

namespace App\Filament\Resources;

use App\Models\Student;
use App\Services\MonitoringService;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends InternalResource
{
    protected static ?string $model = Student::class;

    protected static ?string $modelLabel = 'Mahasantri';

    protected static ?string $pluralModelLabel = 'Mahasantri';

    protected static ?string $recordTitleAttribute = 'nim';

    protected static string $viewPermission = 'students.view';

    protected static string $managePermission = 'students.manage';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')->relationship('user', 'name', fn (Builder $query) => $query->where('role', 'mahasantri')
                ->when(! auth()->user()->hasRole('super_admin'), fn (Builder $users) => $users->whereIn('users.id', app(MonitoringService::class)->students(auth()->user())->select('user_id'))))->required()->unique(ignoreRecord: true)->searchable()->preload(),
            Select::make('group_id')->relationship('group', 'name', fn (Builder $query) => $query->whereIn('groups.id', app(MonitoringService::class)->groups(auth()->user())->select('groups.id')))->required()->searchable()->preload(),
            TextInput::make('nim')->label('NIM')->required()->unique(ignoreRecord: true)->maxLength(255),
            TextInput::make('faculty')->label('Fakultas')->maxLength(255),
            TextInput::make('study_program')->label('Program studi')->maxLength(255),
            TextInput::make('phone')->label('Telepon')->tel()->maxLength(255),
            DatePicker::make('date_of_birth')->label('Tanggal lahir'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('nim')->label('NIM')->searchable()->sortable(),
            TextColumn::make('user.name')->label('Nama')->searchable()->sortable(),
            TextColumn::make('group.name')->label('Kelompok')->searchable()->sortable(),
            TextColumn::make('faculty')->label('Fakultas')->searchable()->sortable(),
        ])->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return app(MonitoringService::class)->students(auth()->user())->with(['user', 'group']);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageStudents::route('/')];
    }
}
