<?php

namespace App\Filament\Widgets;

use App\Enums\TradeStatus;
use App\Models\InventoryItem;
use App\Models\Trade;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Admin Snapshot';

    public static function canView(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isAdmin();
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::query()->count())
                ->description('All registered accounts')
                ->color('info')
                ->icon('heroicon-o-users'),
            Stat::make(
                'Open Trades',
                Trade::query()
                    ->where('status', TradeStatus::Open->value)
                    ->where(fn ($query) => $query
                        ->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now()))
                    ->count()
            )
                ->description('Currently active deals')
                ->color('warning')
                ->icon('heroicon-o-arrows-right-left'),
            Stat::make(
                'Expiring < 6h',
                Trade::query()
                    ->where('status', TradeStatus::Open->value)
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '>=', now())
                    ->where('expires_at', '<=', now()->addHours(6))
                    ->count()
            )
                ->description('Need moderation attention')
                ->color('danger')
                ->icon('heroicon-o-clock'),
            Stat::make('Locked Items', InventoryItem::query()->where('locked', true)->count())
                ->description('Reserved or manually locked')
                ->color('success')
                ->icon('heroicon-o-lock-closed'),
        ];
    }
}
