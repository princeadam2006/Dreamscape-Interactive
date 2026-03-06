<?php

namespace App\Filament\Resources\InventoryItems;

use App\Enums\ItemRarity;
use App\Enums\ItemType;
use App\Enums\TradeStatus;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static ?string $slug = 'inventory';

    protected static ?string $navigationLabel = 'Inventory';

    protected static string|\UnitEnum|null $navigationGroup = 'Trading';

    protected static ?int $navigationSort = 15;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.username')
                    ->label('Owner')
                    ->searchable()
                    ->toggleable()
                    ->visible(fn (): bool => static::isAdminUser()),
                TextColumn::make('item.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('item.type')
                    ->badge()
                    ->searchable()
                    ->formatStateUsing(fn (BackedEnum|string|null $state): string => $state instanceof BackedEnum ? (string) $state->value : (string) $state)
                    ->color(fn (ItemType|string|null $state): string => ($state instanceof ItemType ? $state : ItemType::tryFrom((string) $state))?->color() ?? 'gray')
                    ->sortable(),
                TextColumn::make('item.rarity')
                    ->badge()
                    ->searchable()
                    ->formatStateUsing(fn (BackedEnum|string|null $state): string => $state instanceof BackedEnum ? (string) $state->value : (string) $state)
                    ->color(fn (ItemRarity|string|null $state): string => ($state instanceof ItemRarity ? $state : ItemRarity::tryFrom((string) $state))?->color() ?? 'gray')
                    ->sortable(),
                TextColumn::make('item.required_level')
                    ->label('Required level')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('item.power')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('item.speed')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('item.durability')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('locked')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('has_open_trade')
                    ->label('In Open Trade')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Acquired')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('locked')
                    ->label('Locked'),
                TernaryFilter::make('has_open_trade')
                    ->label('In open trade')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('tradeItems.trade', fn (Builder $query): Builder => $query->where('status', TradeStatus::Open->value)),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('tradeItems.trade', fn (Builder $query): Builder => $query->where('status', TradeStatus::Open->value)),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                SelectFilter::make('owner')
                    ->label('Owner')
                    ->relationship('user', 'username')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => static::isAdminUser()),
                SelectFilter::make('type')
                    ->label('Type')
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
                    ->query(function (Builder $query, array $data): Builder {
                        $type = $data['value'] ?? null;

                        return $query->when(
                            filled($type),
                            fn (Builder $query): Builder => $query->whereRelation('item', 'type', $type),
                        );
                    }),
                SelectFilter::make('rarity')
                    ->label('Rarity')
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
                    ->query(function (Builder $query, array $data): Builder {
                        $rarity = $data['value'] ?? null;

                        return $query->when(
                            filled($rarity),
                            fn (Builder $query): Builder => $query->whereRelation('item', 'rarity', $rarity),
                        );
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No inventory items yet')
            ->emptyStateDescription('Items you collect or assign will appear here.')
            ->recordActions([
                Action::make('drop')
                    ->label('Drop')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Drop item')
                    ->modalDescription('This permanently removes the item from your inventory.')
                    ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->isPlayer())
                    ->disabled(fn (InventoryItem $record): bool => static::getDropRestrictionMessage($record) !== null)
                    ->action(function (InventoryItem $record): void {
                        $reason = static::getDropRestrictionMessage($record);

                        if ($reason !== null) {
                            FilamentNotification::make()
                                ->danger()
                                ->title($reason)
                                ->send();

                            return;
                        }

                        $record->delete();

                        FilamentNotification::make()
                            ->success()
                            ->title('Item dropped.')
                            ->send();
                    }),
                Action::make('toggleLock')
                    ->label(fn (InventoryItem $record): string => $record->locked ? 'Unlock' : 'Lock')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => static::isAdminUser())
                    ->action(function (InventoryItem $record): void {
                        $actor = auth()->user();

                        if (! $actor instanceof User || ! $actor->isAdmin()) {
                            abort(403);
                        }

                        $hasOpenTrade = static::hasOpenTrade($record);
                        $newLockState = ! $record->locked;

                        if ($hasOpenTrade && ! $newLockState) {
                            FilamentNotification::make()
                                ->danger()
                                ->title('Cannot unlock item while it is in an open trade.')
                                ->send();

                            return;
                        }

                        $record->update([
                            'locked' => $newLockState,
                        ]);

                        static::createInventoryAudit(
                            actor: $actor,
                            action: $newLockState ? 'inventory.locked' : 'inventory.unlocked',
                            record: $record,
                            targetUserId: $record->user_id,
                            reason: 'Admin lock state change',
                        );

                        FilamentNotification::make()
                            ->success()
                            ->title($newLockState ? 'Item locked.' : 'Item unlocked.')
                            ->send();
                    }),
                Action::make('transferOwnership')
                    ->label('Transfer')
                    ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                    ->color('info')
                    ->visible(fn (): bool => static::isAdminUser())
                    ->form([
                        Select::make('new_user_id')
                            ->label('Transfer to user')
                            ->options(fn (): array => User::query()->orderBy('username')->pluck('username', 'id')->all())
                            ->searchable()
                            ->required(),
                        Textarea::make('reason')
                            ->rows(3)
                            ->maxLength(500)
                            ->required(),
                    ])
                    ->action(function (InventoryItem $record, array $data): void {
                        $actor = auth()->user();

                        if (! $actor instanceof User || ! $actor->isAdmin()) {
                            abort(403);
                        }

                        if (static::hasOpenTrade($record)) {
                            FilamentNotification::make()
                                ->danger()
                                ->title('Cancel the open trade first before transferring this item.')
                                ->send();

                            return;
                        }

                        $targetUserId = (int) ($data['new_user_id'] ?? 0);

                        if ($targetUserId <= 0 || $targetUserId === $record->user_id) {
                            FilamentNotification::make()
                                ->danger()
                                ->title('Select a different owner.')
                                ->send();

                            return;
                        }

                        $record->update([
                            'user_id' => $targetUserId,
                            'locked' => false,
                        ]);

                        static::createInventoryAudit(
                            actor: $actor,
                            action: 'inventory.transferred',
                            record: $record,
                            targetUserId: $targetUserId,
                            reason: (string) $data['reason'],
                        );

                        FilamentNotification::make()
                            ->title('Inventory Item Transferred')
                            ->body('An administrator transferred an item into your inventory.')
                            ->info()
                            ->sendToDatabase(User::query()->findOrFail($targetUserId), isEventDispatched: true);

                        FilamentNotification::make()
                            ->success()
                            ->title('Item transferred.')
                            ->send();
                    }),
                Action::make('removeInventoryItem')
                    ->label('Remove')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->visible(fn (): bool => static::isAdminUser())
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reason')
                            ->rows(3)
                            ->maxLength(500)
                            ->required(),
                    ])
                    ->action(function (InventoryItem $record, array $data): void {
                        $actor = auth()->user();

                        if (! $actor instanceof User || ! $actor->isAdmin()) {
                            abort(403);
                        }

                        if (static::hasOpenTrade($record)) {
                            FilamentNotification::make()
                                ->danger()
                                ->title('Cancel the open trade first before removing this item.')
                                ->send();

                            return;
                        }

                        $ownerId = $record->user_id;
                        $itemId = $record->item_id;
                        $recordId = $record->id;
                        $record->delete();

                        AuditLog::query()->create([
                            'user_id' => $actor->id,
                            'action' => 'inventory.removed',
                            'target_user_id' => $ownerId,
                            'target_item_id' => $itemId,
                            'meta' => [
                                'inventory_item_id' => $recordId,
                                'reason' => (string) $data['reason'],
                            ],
                            'created_at' => now(),
                        ]);

                        $owner = User::query()->find($ownerId);

                        if ($owner instanceof User) {
                            FilamentNotification::make()
                                ->title('Inventory Item Removed')
                                ->body('An administrator removed an item from your inventory.')
                                ->warning()
                                ->sendToDatabase($owner, isEventDispatched: true);
                        }

                        FilamentNotification::make()
                            ->success()
                            ->title('Item removed.')
                            ->send();
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['item', 'user'])
            ->withExists([
                'tradeItems as has_open_trade' => fn (Builder $query): Builder => $query->whereHas(
                    'trade',
                    fn (Builder $query): Builder => $query->where('status', TradeStatus::Open->value)
                ),
            ]);

        if (static::isAdminUser()) {
            return $query;
        }

        return $query->where('user_id', auth()->id() ?? 0);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->can('ViewAny:InventoryItem')
            && ($user->isPlayer() || $user->isAdmin());
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

    protected static function getDropRestrictionMessage(InventoryItem $record): ?string
    {
        if ($record->locked) {
            return 'This item is locked and cannot be dropped.';
        }

        if (static::hasOpenTrade($record)) {
            return 'This item is part of an open trade and cannot be dropped.';
        }

        return null;
    }

    protected static function hasOpenTrade(InventoryItem $record): bool
    {
        return (bool) ($record->has_open_trade ?? false)
            || $record->tradeItems()
                ->whereHas('trade', fn (Builder $query): Builder => $query->where('status', TradeStatus::Open->value))
                ->exists();
    }

    protected static function isAdminUser(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isAdmin();
    }

    protected static function createInventoryAudit(
        User $actor,
        string $action,
        InventoryItem $record,
        ?int $targetUserId,
        string $reason
    ): void {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'action' => $action,
            'target_user_id' => $targetUserId,
            'target_item_id' => $record->item_id,
            'meta' => [
                'inventory_item_id' => $record->id,
                'reason' => $reason,
            ],
            'created_at' => now(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInventoryItems::route('/'),
        ];
    }
}
