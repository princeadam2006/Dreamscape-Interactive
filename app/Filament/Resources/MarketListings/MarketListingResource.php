<?php

namespace App\Filament\Resources\MarketListings;

use App\Enums\ItemRarity;
use App\Enums\ItemType;
use App\Enums\TradeStatus;
use App\Filament\Resources\MarketListings\Pages\ManageMarketListings;
use App\Filament\Resources\Trades\TradeResource;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MarketListingResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static ?string $slug = 'market';

    protected static ?string $navigationLabel = 'Market';

    protected static string|\UnitEnum|null $navigationGroup = 'Trading';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.username')
                    ->label('Owner')
                    ->searchable(),
                TextColumn::make('item.name')
                    ->label('Item')
                    ->searchable(),
                TextColumn::make('item.type')
                    ->badge()
                    ->formatStateUsing(fn (BackedEnum|string|null $state): string => $state instanceof BackedEnum ? (string) $state->value : (string) $state)
                    ->color(fn (ItemType|string|null $state): string => ($state instanceof ItemType ? $state : ItemType::tryFrom((string) $state))?->color() ?? 'gray'),
                TextColumn::make('item.rarity')
                    ->badge()
                    ->formatStateUsing(fn (BackedEnum|string|null $state): string => $state instanceof BackedEnum ? (string) $state->value : (string) $state)
                    ->color(fn (ItemRarity|string|null $state): string => ($state instanceof ItemRarity ? $state : ItemRarity::tryFrom((string) $state))?->color() ?? 'gray'),
                TextColumn::make('item.power')
                    ->sortable(),
                TextColumn::make('item.speed')
                    ->sortable(),
                TextColumn::make('item.durability')
                    ->sortable(),
                IconColumn::make('locked')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(fn (): array => Item::query()
                        ->select('type')
                        ->get()
                        ->pluck('type')
                        ->map(fn (BackedEnum|string|null $type): string => $type instanceof BackedEnum ? (string) $type->value : (string) $type)
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->mapWithKeys(fn (string $type): array => [$type => $type])
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->whereRelation('item', 'type', $data['value'])
                    )),
                SelectFilter::make('rarity')
                    ->options(fn (): array => Item::query()
                        ->select('rarity')
                        ->get()
                        ->pluck('rarity')
                        ->map(fn (BackedEnum|string|null $rarity): string => $rarity instanceof BackedEnum ? (string) $rarity->value : (string) $rarity)
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->mapWithKeys(fn (string $rarity): array => [$rarity => $rarity])
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->whereRelation('item', 'rarity', $data['value'])
                    )),
                TernaryFilter::make('locked')
                    ->label('Locked'),
            ])
            ->recordActions([
                Action::make('proposeTrade')
                    ->label('Propose Trade')
                    ->url(fn (InventoryItem $record): string => TradeResource::getUrl('create', [
                        'receiver' => $record->user_id,
                        'requested_item' => $record->id,
                    ]))
                    ->disabled(fn (InventoryItem $record): bool => $record->locked || (bool) ($record->has_open_trade ?? false)),
            ])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No market items available');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', '!=', auth()->id() ?? 0)
            ->whereHas('item', fn (Builder $query): Builder => $query->where('tradeable_default', true))
            ->with(['user', 'item'])
            ->withExists([
                'tradeItems as has_open_trade' => fn (Builder $query): Builder => $query->whereHas(
                    'trade',
                    fn (Builder $query): Builder => $query->where('status', TradeStatus::Open->value)
                ),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->isPlayer()
            && auth()->user()->can('ViewAny:InventoryItem');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMarketListings::route('/'),
        ];
    }
}
