<?php

namespace App\Filament\Resources\Trades;

use App\Enums\NotificationType;
use App\Enums\TradeItemRole;
use App\Enums\TradeStatus;
use App\Filament\Resources\Trades\Pages\CreateTrade;
use App\Filament\Resources\Trades\Pages\ListTrades;
use App\Filament\Resources\Trades\Schemas\TradeForm;
use App\Filament\Resources\Trades\Tables\TradesTable;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\Trade;
use App\Models\TradeItem;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class TradeResource extends Resource
{
    protected static ?string $model = Trade::class;

    protected static ?string $navigationLabel = 'Trades';

    protected static string|\UnitEnum|null $navigationGroup = 'Trading';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    public static function form(Schema $schema): Schema
    {
        return TradeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TradesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        $query = Trade::query()->where('status', TradeStatus::Open->value);

        $query->where(fn (Builder $query): Builder => $query
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now()));

        if ($user->isPlayer()) {
            $query->where(fn (Builder $query): Builder => $query
                ->where('initiator_user_id', $user->id)
                ->orWhere('receiver_user_id', $user->id));
        }

        $openTradesCount = $query->count();

        return $openTradesCount > 0 ? (string) $openTradesCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        static::expireDueTrades($user instanceof User ? $user : null);

        $query = parent::getEloquentQuery()->with([
            'initiator',
            'receiver',
            'tradeItems.inventoryItem.item',
        ]);

        if (! $user instanceof User) {
            return $query->whereKey(0);
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(fn (Builder $query): Builder => $query
            ->where('initiator_user_id', $user->id)
            ->orWhere('receiver_user_id', $user->id));
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->can('ViewAny:Trade')
            && ($user->isPlayer() || $user->isAdmin());
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->can('View:Trade')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPlayer() && in_array($user->id, [$record->initiator_user_id, $record->receiver_user_id], true);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->isPlayer()
            && $user->can('Create:Trade');
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

    /**
     * @return array<int, string>
     */
    public static function availableInventoryItemOptionsForUser(?int $userId): array
    {
        if (! $userId) {
            return [];
        }

        $user = auth()->user();
        static::expireDueTrades($user instanceof User ? $user : null);

        return static::availableInventoryItemsQuery($userId)
            ->with('item')
            ->get()
            ->sortBy(fn (InventoryItem $inventoryItem): string => (string) ($inventoryItem->item?->name ?? ''))
            ->mapWithKeys(function (InventoryItem $inventoryItem): array {
                $item = $inventoryItem->item;
                $itemName = (string) ($item?->name ?? 'Unknown item');
                $rarity = (string) ($item?->rarity?->value ?? 'unknown');

                return [
                    $inventoryItem->id => "{$itemName} ({$rarity})",
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function createTradeProposal(array $data, User $initiator): Trade
    {
        static::expireDueTrades($initiator);

        $receiverId = (int) ($data['receiver_user_id'] ?? 0);
        $offeredItemIds = collect($data['offered_item_ids'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $requestedItemIds = collect($data['requested_item_ids'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $receiver = User::query()->find($receiverId);

        if (! $receiver instanceof User || ! $receiver->isPlayer() || $receiver->id === $initiator->id) {
            throw ValidationException::withMessages([
                'data.receiver_user_id' => 'Please choose a valid player to trade with.',
            ]);
        }

        if ($offeredItemIds === []) {
            throw ValidationException::withMessages([
                'data.offered_item_ids' => 'Select at least one offered item.',
            ]);
        }

        if ($requestedItemIds === []) {
            throw ValidationException::withMessages([
                'data.requested_item_ids' => 'Select at least one requested item.',
            ]);
        }

        try {
            /** @var Trade $trade */
            $trade = DB::transaction(function () use ($data, $initiator, $offeredItemIds, $receiver, $requestedItemIds): Trade {
                $offeredItems = static::availableInventoryItemsQuery($initiator->id, $offeredItemIds)
                    ->lockForUpdate()
                    ->get();
                $requestedItems = static::availableInventoryItemsQuery($receiver->id, $requestedItemIds)
                    ->lockForUpdate()
                    ->get();

                if ($offeredItems->count() !== count($offeredItemIds)) {
                    throw ValidationException::withMessages([
                        'data.offered_item_ids' => 'One or more offered items are no longer available.',
                    ]);
                }

                if ($requestedItems->count() !== count($requestedItemIds)) {
                    throw ValidationException::withMessages([
                        'data.requested_item_ids' => 'One or more requested items are no longer available.',
                    ]);
                }

                $trade = Trade::query()->create([
                    'initiator_user_id' => $initiator->id,
                    'receiver_user_id' => $receiver->id,
                    'status' => TradeStatus::Open->value,
                    'message' => filled($data['message'] ?? null) ? (string) $data['message'] : null,
                    'expires_at' => now()->addHours(48),
                ]);

                foreach ($offeredItems as $offeredItem) {
                    TradeItem::query()->create([
                        'trade_id' => $trade->id,
                        'inventory_item_id' => $offeredItem->id,
                        'user_id' => $initiator->id,
                        'role_in_trade' => TradeItemRole::Offer->value,
                    ]);
                }

                foreach ($requestedItems as $requestedItem) {
                    TradeItem::query()->create([
                        'trade_id' => $trade->id,
                        'inventory_item_id' => $requestedItem->id,
                        'user_id' => $receiver->id,
                        'role_in_trade' => TradeItemRole::Request->value,
                    ]);
                }

                static::lockInventoryItems($offeredItems->merge($requestedItems));

                return $trade->fresh(['initiator', 'receiver']) ?? $trade;
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw ValidationException::withMessages([
                'data.receiver_user_id' => 'Trade proposal could not be created. Please try again.',
            ]);
        }

        static::logTradeAudit(
            actor: $initiator,
            action: 'trade.proposed',
            trade: $trade,
            targetUserId: $receiver->id,
            meta: [
                'offered_item_ids' => $offeredItemIds,
                'requested_item_ids' => $requestedItemIds,
                'expires_at' => $trade->expires_at?->toIso8601String(),
            ],
        );

        static::notifyTradeUpdate(
            user: $receiver,
            type: NotificationType::TradeCreated,
            message: "New trade proposal from {$initiator->username}.",
            context: ['trade_id' => $trade->id]
        );

        return $trade;
    }

    public static function acceptTrade(Trade $trade, User $actor): void
    {
        if ($trade->receiver_user_id !== $actor->id) {
            throw ValidationException::withMessages([
                'data.receiver_user_id' => 'Only the receiver can accept this trade.',
            ]);
        }

        if (static::expireTradeIfDue($trade, $actor)) {
            throw ValidationException::withMessages([
                'data.receiver_user_id' => 'This trade has expired and can no longer be accepted.',
            ]);
        }

        $trade = DB::transaction(function () use ($trade): Trade {
            $trade = static::loadTradeForTransition($trade->id);

            if ($trade->status !== TradeStatus::Open) {
                throw ValidationException::withMessages([
                    'data.receiver_user_id' => 'Only open trades can be accepted.',
                ]);
            }

            $tradeItems = $trade->tradeItems;

            if ($tradeItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'data.receiver_user_id' => 'This trade has no items to exchange.',
                ]);
            }

            foreach ($tradeItems as $tradeItem) {
                $inventoryItem = $tradeItem->inventoryItem;

                if (! $inventoryItem instanceof InventoryItem || $inventoryItem->user_id !== $tradeItem->user_id) {
                    throw ValidationException::withMessages([
                        'data.receiver_user_id' => 'One or more trade items are no longer valid.',
                    ]);
                }
            }

            foreach ($tradeItems as $tradeItem) {
                $targetUserId = $tradeItem->role_in_trade === TradeItemRole::Offer
                    ? $trade->receiver_user_id
                    : $trade->initiator_user_id;

                $tradeItem->inventoryItem->update([
                    'user_id' => $targetUserId,
                    'locked' => false,
                ]);
            }

            $trade->update([
                'status' => TradeStatus::Accepted->value,
            ]);

            return $trade;
        });

        $trade->refresh()->loadMissing(['initiator', 'receiver']);

        static::logTradeAudit(
            actor: $actor,
            action: 'trade.accepted',
            trade: $trade,
            targetUserId: $trade->initiator_user_id,
        );

        static::notifyTradeUpdate(
            user: $trade->initiator,
            type: NotificationType::TradeAccepted,
            message: "Your trade #{$trade->id} was accepted by {$trade->receiver->username}.",
            context: ['trade_id' => $trade->id]
        );
        static::notifyTradeUpdate(
            user: $trade->receiver,
            type: NotificationType::TradeAccepted,
            message: "You accepted trade #{$trade->id}.",
            context: ['trade_id' => $trade->id]
        );
    }

    public static function rejectTrade(Trade $trade, User $actor): void
    {
        if ($trade->receiver_user_id !== $actor->id) {
            throw ValidationException::withMessages([
                'data.receiver_user_id' => 'Only the receiver can reject this trade.',
            ]);
        }

        if (static::expireTradeIfDue($trade, $actor)) {
            throw ValidationException::withMessages([
                'data.receiver_user_id' => 'This trade has expired and can no longer be rejected.',
            ]);
        }

        $trade = DB::transaction(function () use ($trade): Trade {
            $trade = static::loadTradeForTransition($trade->id);

            if ($trade->status !== TradeStatus::Open) {
                throw ValidationException::withMessages([
                    'data.receiver_user_id' => 'Only open trades can be rejected.',
                ]);
            }

            $trade->update([
                'status' => TradeStatus::Rejected->value,
            ]);

            static::unlockTradeItems($trade);

            return $trade;
        });

        $trade->refresh()->loadMissing(['initiator', 'receiver']);

        static::logTradeAudit(
            actor: $actor,
            action: 'trade.rejected',
            trade: $trade,
            targetUserId: $trade->initiator_user_id,
        );

        static::notifyTradeUpdate(
            user: $trade->initiator,
            type: NotificationType::TradeRejected,
            message: "Your trade #{$trade->id} was rejected by {$trade->receiver->username}.",
            context: ['trade_id' => $trade->id]
        );
        static::notifyTradeUpdate(
            user: $trade->receiver,
            type: NotificationType::TradeRejected,
            message: "You rejected trade #{$trade->id}.",
            context: ['trade_id' => $trade->id]
        );
    }

    public static function cancelTrade(Trade $trade, User $actor): void
    {
        if ($trade->initiator_user_id !== $actor->id) {
            throw ValidationException::withMessages([
                'data.receiver_user_id' => 'Only the initiator can cancel this trade.',
            ]);
        }

        if (static::expireTradeIfDue($trade, $actor)) {
            throw ValidationException::withMessages([
                'data.receiver_user_id' => 'This trade has expired and can no longer be canceled.',
            ]);
        }

        $trade = DB::transaction(function () use ($trade): Trade {
            $trade = static::loadTradeForTransition($trade->id);

            if ($trade->status !== TradeStatus::Open) {
                throw ValidationException::withMessages([
                    'data.receiver_user_id' => 'Only open trades can be canceled.',
                ]);
            }

            $trade->update([
                'status' => TradeStatus::Canceled->value,
            ]);

            static::unlockTradeItems($trade);

            return $trade;
        });

        $trade->refresh()->loadMissing(['initiator', 'receiver']);

        static::logTradeAudit(
            actor: $actor,
            action: 'trade.canceled',
            trade: $trade,
            targetUserId: $trade->receiver_user_id,
        );

        static::notifyTradeUpdate(
            user: $trade->receiver,
            type: NotificationType::TradeCanceled,
            message: "{$trade->initiator->username} canceled trade #{$trade->id}.",
            context: ['trade_id' => $trade->id]
        );
        static::notifyTradeUpdate(
            user: $trade->initiator,
            type: NotificationType::TradeCanceled,
            message: "You canceled trade #{$trade->id}.",
            context: ['trade_id' => $trade->id]
        );
    }

    public static function forceCancelTrade(Trade $trade, User $actor): void
    {
        if (! $actor->isAdmin() || ! $actor->can('Update:Trade')) {
            abort(403);
        }

        if (static::expireTradeIfDue($trade, $actor)) {
            throw ValidationException::withMessages([
                'data.receiver_user_id' => 'This trade was already expired.',
            ]);
        }

        $trade = DB::transaction(function () use ($trade): Trade {
            $trade = static::loadTradeForTransition($trade->id);

            if ($trade->status !== TradeStatus::Open) {
                throw ValidationException::withMessages([
                    'data.receiver_user_id' => 'Only open trades can be force-canceled.',
                ]);
            }

            $trade->update([
                'status' => TradeStatus::Canceled->value,
            ]);

            static::unlockTradeItems($trade);

            return $trade;
        });

        $trade->refresh()->loadMissing(['initiator', 'receiver']);

        static::logTradeAudit(
            actor: $actor,
            action: 'trade.force_canceled',
            trade: $trade,
            targetUserId: $trade->receiver_user_id,
        );

        static::notifyTradeUpdate(
            user: $trade->initiator,
            type: NotificationType::TradeCanceled,
            message: "Trade #{$trade->id} was canceled by an administrator.",
            context: ['trade_id' => $trade->id]
        );
        static::notifyTradeUpdate(
            user: $trade->receiver,
            type: NotificationType::TradeCanceled,
            message: "Trade #{$trade->id} was canceled by an administrator.",
            context: ['trade_id' => $trade->id]
        );
    }

    /**
     * @param  array<int>  $itemIds
     */
    private static function availableInventoryItemsQuery(int $userId, array $itemIds = []): Builder
    {
        $query = InventoryItem::query()
            ->where('user_id', $userId)
            ->where('locked', false)
            ->whereRelation('item', 'tradeable_default', true)
            ->whereDoesntHave('tradeItems.trade', fn (Builder $query): Builder => $query->where('status', TradeStatus::Open->value));

        if ($itemIds !== []) {
            $query->whereIn('id', $itemIds);
        }

        return $query;
    }

    private static function loadTradeForTransition(int $tradeId): Trade
    {
        return Trade::query()
            ->whereKey($tradeId)
            ->lockForUpdate()
            ->with(['tradeItems.inventoryItem.item', 'initiator', 'receiver'])
            ->firstOrFail();
    }

    private static function expireDueTrades(?User $triggeredBy): void
    {
        $dueTradeIds = Trade::query()
            ->where('status', TradeStatus::Open->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->limit(25)
            ->pluck('id')
            ->all();

        foreach ($dueTradeIds as $dueTradeId) {
            $trade = Trade::query()->find($dueTradeId);

            if (! $trade instanceof Trade) {
                continue;
            }

            static::expireTradeIfDue($trade, $triggeredBy);
        }
    }

    private static function expireTradeIfDue(Trade $trade, ?User $triggeredBy): bool
    {
        if (! static::isDueForExpiration($trade)) {
            return false;
        }

        /** @var Trade|null $expiredTrade */
        $expiredTrade = DB::transaction(function () use ($trade): ?Trade {
            $trade = static::loadTradeForTransition($trade->id);

            if ($trade->status !== TradeStatus::Open || ! static::isDueForExpiration($trade)) {
                return null;
            }

            $trade->update([
                'status' => TradeStatus::Expired->value,
            ]);

            static::unlockTradeItems($trade);

            return $trade;
        });

        if (! $expiredTrade instanceof Trade) {
            return false;
        }

        $expiredTrade->refresh()->loadMissing(['initiator', 'receiver']);

        static::notifyTradeUpdate(
            user: $expiredTrade->initiator,
            type: NotificationType::TradeExpired,
            message: "Trade #{$expiredTrade->id} expired before completion.",
            context: ['trade_id' => $expiredTrade->id]
        );
        static::notifyTradeUpdate(
            user: $expiredTrade->receiver,
            type: NotificationType::TradeExpired,
            message: "Trade #{$expiredTrade->id} expired before completion.",
            context: ['trade_id' => $expiredTrade->id]
        );

        if ($triggeredBy instanceof User) {
            static::logTradeAudit(
                actor: $triggeredBy,
                action: 'trade.expired',
                trade: $expiredTrade,
                targetUserId: $expiredTrade->receiver_user_id,
            );
        }

        return true;
    }

    private static function isDueForExpiration(Trade $trade): bool
    {
        return $trade->status === TradeStatus::Open
            && $trade->expires_at !== null
            && $trade->expires_at->isPast();
    }

    /**
     * @param  Collection<int, InventoryItem>  $inventoryItems
     */
    private static function lockInventoryItems(Collection $inventoryItems): void
    {
        foreach ($inventoryItems as $inventoryItem) {
            $inventoryItem->update([
                'locked' => true,
            ]);
        }
    }

    private static function unlockTradeItems(Trade $trade): void
    {
        foreach ($trade->tradeItems as $tradeItem) {
            $inventoryItem = $tradeItem->inventoryItem;

            if (! $inventoryItem instanceof InventoryItem) {
                continue;
            }

            if (! $inventoryItem->locked) {
                continue;
            }

            $inventoryItem->update([
                'locked' => false,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function notifyTradeUpdate(User $user, NotificationType $type, string $message, array $context = []): void
    {
        $notification = FilamentNotification::make()
            ->title($type->label())
            ->body($message)
            ->viewData([
                'context' => $context,
                'notification_type' => $type->value,
            ]);

        match ($type->color()) {
            'success' => $notification->success(),
            'warning' => $notification->warning(),
            'danger' => $notification->danger(),
            default => $notification->info(),
        };

        $notification->sendToDatabase($user, isEventDispatched: true);

        if (! $user->canReceiveTradeUpdateEmails()) {
            return;
        }

        Mail::raw(
            $message,
            function ($mail) use ($type, $user): void {
                $mail->to($user->email)
                    ->subject("Dreamscape trade update: {$type->label()}");
            }
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private static function logTradeAudit(User $actor, string $action, Trade $trade, ?int $targetUserId = null, array $meta = []): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'action' => $action,
            'target_user_id' => $targetUserId,
            'target_item_id' => null,
            'meta' => [
                'trade_id' => $trade->id,
                ...$meta,
            ],
            'created_at' => now(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrades::route('/'),
            'create' => CreateTrade::route('/create'),
        ];
    }
}
