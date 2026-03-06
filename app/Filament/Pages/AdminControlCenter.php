<?php

namespace App\Filament\Pages;

use App\Enums\TradeStatus;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\InventoryItems\InventoryItemResource;
use App\Filament\Resources\Items\ItemResource;
use App\Filament\Resources\Trades\TradeResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\Trade;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class AdminControlCenter extends Page
{
    protected static ?string $navigationLabel = 'Control Center';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 0;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCommandLine;

    protected static ?string $slug = 'admin-control-center';

    protected string $view = 'filament.pages.admin-control-center';

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isAdmin();
    }

    /**
     * @return array<int, array{label: string, value: string, tone: string}>
     */
    public function getOverviewStats(): array
    {
        return [
            [
                'label' => 'Total Users',
                'value' => (string) User::query()->count(),
                'tone' => 'cyan',
            ],
            [
                'label' => 'Players',
                'value' => (string) User::query()
                    ->whereHas('roles', fn ($query) => $query->where('name', 'player'))
                    ->count(),
                'tone' => 'emerald',
            ],
            [
                'label' => 'Open Trades',
                'value' => (string) Trade::query()
                    ->where('status', TradeStatus::Open->value)
                    ->where(fn ($query) => $query
                        ->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now()))
                    ->count(),
                'tone' => 'amber',
            ],
            [
                'label' => 'Locked Items',
                'value' => (string) InventoryItem::query()->where('locked', true)->count(),
                'tone' => 'rose',
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, description: string, url: string}>
     */
    public function getQuickLinks(): array
    {
        return [
            [
                'label' => 'Users',
                'description' => 'Manage player accounts and roles.',
                'url' => UserResource::getUrl(),
            ],
            [
                'label' => 'Trades',
                'description' => 'Moderate and force-cancel risky deals.',
                'url' => TradeResource::getUrl(),
            ],
            [
                'label' => 'Inventory',
                'description' => 'Lock, transfer, and remove ownership records.',
                'url' => InventoryItemResource::getUrl(),
            ],
            [
                'label' => 'Item Catalog',
                'description' => 'Tune item attributes and tradability.',
                'url' => ItemResource::getUrl(),
            ],
            [
                'label' => 'Audit Logs',
                'description' => 'Review every sensitive admin action.',
                'url' => AuditLogResource::getUrl(),
            ],
        ];
    }

    /**
     * @return Collection<int, Trade>
     */
    public function getExpiringTrades(): Collection
    {
        return Trade::query()
            ->where('status', TradeStatus::Open->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>=', now())
            ->where('expires_at', '<=', now()->addHours(6))
            ->with(['initiator', 'receiver'])
            ->orderBy('expires_at')
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, AuditLog>
     */
    public function getRecentAuditLogs(): Collection
    {
        return AuditLog::query()
            ->with(['user', 'targetUser'])
            ->latest('created_at')
            ->limit(8)
            ->get();
    }
}
