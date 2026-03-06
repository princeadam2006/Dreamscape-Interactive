<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\InventoryItems\InventoryItemResource;
use App\Filament\Resources\Items\ItemResource;
use App\Filament\Resources\MarketListings\MarketListingResource;
use App\Filament\Resources\Trades\TradeResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\AdminOverview;
use App\Filament\Widgets\OwnershipInsightsWidget;
use App\Filament\Widgets\PlayerOverview;
use App\Filament\Widgets\RecentTrades;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';

    public function getTitle(): string|Htmlable
    {
        return 'Dreamscape Control Room';
    }

    public function getHeading(): string|Htmlable
    {
        $user = auth()->user();

        if ($user instanceof User) {
            return "Welcome back, {$user->name}";
        }

        return 'Welcome';
    }

    public function getSubheading(): ?string
    {
        $user = auth()->user();

        if ($user instanceof User && $user->isAdmin()) {
            return 'Monitor system health, trade flow, and player activity in one place.';
        }

        return 'Track your inventory, offers, and market opportunities from a single hub.';
    }

    /**
     * @return array<class-string<Widget>>
     */
    public function getWidgets(): array
    {
        $user = auth()->user();

        if ($user instanceof User && $user->isAdmin()) {
            return [
                AdminOverview::class,
                OwnershipInsightsWidget::class,
                RecentTrades::class,
            ];
        }

        return [
            PlayerOverview::class,
            RecentTrades::class,
        ];
    }

    /**
     * @return array<int, array{label: string, description: string, url: string}>
     */
    public function getQuickLinks(): array
    {
        $user = auth()->user();

        if ($user instanceof User && $user->isAdmin()) {
            return [
                [
                    'label' => 'User Management',
                    'description' => 'Manage players, roles, and permissions.',
                    'url' => UserResource::getUrl(),
                ],
                [
                    'label' => 'Trades Oversight',
                    'description' => 'Review and moderate trade activity.',
                    'url' => TradeResource::getUrl(),
                ],
                [
                    'label' => 'Inventory Oversight',
                    'description' => 'Lock, transfer, or remove inventory items.',
                    'url' => InventoryItemResource::getUrl(),
                ],
                [
                    'label' => 'Audit Trail',
                    'description' => 'Inspect system-wide admin actions.',
                    'url' => AuditLogResource::getUrl(),
                ],
            ];
        }

        return [
            [
                'label' => 'My Inventory',
                'description' => 'View and manage your owned items.',
                'url' => InventoryItemResource::getUrl(),
            ],
            [
                'label' => 'Market',
                'description' => 'Browse available items from other players.',
                'url' => MarketListingResource::getUrl(),
            ],
            [
                'label' => 'Trades',
                'description' => 'Create and respond to trade proposals.',
                'url' => TradeResource::getUrl(),
            ],
            [
                'label' => 'Catalog',
                'description' => 'Explore item stats and rarity tiers.',
                'url' => ItemResource::getUrl(),
            ],
        ];
    }
}
