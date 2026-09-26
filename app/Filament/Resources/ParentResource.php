<?php

namespace App\Filament\Resources;

use App\Models\ParentModel;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ParentResource extends InternalResource
{
    protected static ?string $model = ParentModel::class;

    protected static ?string $modelLabel = 'Orang Tua';

    protected static ?string $pluralModelLabel = 'Orang Tua';

    protected static string $viewPermission = 'parents.manage';

    protected static string $managePermission = 'parents.manage';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')->relationship('user', 'name', fn ($query) => $query->where('role', 'orang_tua'))->required()->unique(ignoreRecord: true)->searchable()->preload(),
            TextInput::make('phone')->label('Telepon')->maxLength(255), Textarea::make('address')->label('Alamat'),
            Select::make('students')->label('Anak')->relationship('students', 'nim')->multiple()->searchable()->preload()->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('user.name')->label('Nama')->searchable(), TextColumn::make('phone')->label('Telepon'), TextColumn::make('students.nim')->label('Anak')->listWithLineBreaks()])->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageParents::route('/')];
    }
}
