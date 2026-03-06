<?php

namespace App\Filament\Resources\Items;

use App\Filament\Resources\Items\Pages\CreateItem;
use App\Filament\Resources\Items\Pages\EditItem;
use App\Filament\Resources\Items\Pages\ListItems;
use App\Filament\Resources\Items\Pages\ViewItem;
use App\Filament\Resources\Items\Schemas\ItemForm;
use App\Filament\Resources\Items\Schemas\ItemInfolist;
use App\Filament\Resources\Items\Tables\ItemsTable;
use App\Models\Item;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationLabel = 'Item Catalog';

    protected static string|\UnitEnum|null $navigationGroup = 'Trading';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    public static function form(Schema $schema): Schema
    {
        return ItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user instanceof User && $user->isAdmin()) {
            return $query;
        }

        return $query->where('tradeable_default', true);
    }

    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User && auth()->user()->can('ViewAny:Item');
    }

    public static function canView(Model $record): bool
    {
        return auth()->user() instanceof User && auth()->user()->can('View:Item');
    }

    public static function canCreate(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->isAdmin()
            && auth()->user()->can('Create:Item');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->isAdmin()
            && auth()->user()->can('Update:Item');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->isAdmin()
            && auth()->user()->can('Delete:Item');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->isAdmin()
            && auth()->user()->can('DeleteAny:Item');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListItems::route('/'),
            'create' => CreateItem::route('/create'),
            'view' => ViewItem::route('/{record}'),
            'edit' => EditItem::route('/{record}/edit'),
        ];
    }
}
