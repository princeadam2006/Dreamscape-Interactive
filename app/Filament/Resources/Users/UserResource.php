<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'User Management';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('roles');
    }

    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->isAdmin()
            && auth()->user()->can('ViewAny:User');
    }

    public static function canCreate(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->isAdmin()
            && auth()->user()->can('Create:User');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->isAdmin()
            && auth()->user()->can('Update:User');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->isAdmin()
            && auth()->user()->can('Delete:User');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->isAdmin()
            && auth()->user()->can('DeleteAny:User');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
