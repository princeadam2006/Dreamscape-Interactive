<?php

namespace App\Filament\Widgets;

use App\Enums\TradeStatus;
use App\Models\InventoryItem;
use App\Models\Trade;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlayerOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Player Snapshot';

    public static function canView(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isPlayer();
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $openTradesQuery = Trade::query()
            ->where('status', TradeStatus::Open->value)
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->where(fn ($query) => $query
                ->where('initiator_user_id', $user->id)
                ->orWhere('receiver_user_id', $user->id));

        return [
            Stat::make('Inventory Items', InventoryItem::query()->where('user_id', $user->id)->count())
                ->description('Total owned assets')
                ->color('info')
                ->icon('heroicon-o-archive-box'),
            Stat::make('Incoming Offers', (clone $openTradesQuery)->where('receiver_user_id', $user->id)->count())
                ->description('Need your response')
                ->color('warning')
                ->icon('heroicon-o-inbox'),
            Stat::make('Outgoing Offers', (clone $openTradesQuery)->where('initiator_user_id', $user->id)->count())
                ->description('Waiting on counterparties')
                ->color('primary')
                ->icon('heroicon-o-paper-airplane'),
            Stat::make(
                'Unread Alerts',
                $user->unreadNotifications()
                    ->where('data->format', 'filament')
                    ->count()
            )
                ->description('From system and trades')
                ->color('success')
                ->icon('heroicon-o-bell-alert'),
        ];
    }
}
