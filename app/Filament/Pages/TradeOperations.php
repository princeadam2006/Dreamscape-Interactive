<?php

namespace App\Filament\Pages;

use App\Enums\TradeStatus;
use App\Filament\Resources\MarketListings\MarketListingResource;
use App\Filament\Resources\Trades\TradeResource;
use App\Models\Trade;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class TradeOperations extends Page
{
    protected static ?string $navigationLabel = 'Trade Operations';

    protected static string|\UnitEnum|null $navigationGroup = 'Trading';

    protected static ?int $navigationSort = 2;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static ?string $slug = 'trade-operations';

    protected string $view = 'filament.pages.trade-operations';

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isPlayer();
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function getTradeStats(): array
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
            [
                'label' => 'Incoming Open Trades',
                'value' => (string) (clone $openTradesQuery)->where('receiver_user_id', $user->id)->count(),
            ],
            [
                'label' => 'Outgoing Open Trades',
                'value' => (string) (clone $openTradesQuery)->where('initiator_user_id', $user->id)->count(),
            ],
            [
                'label' => 'Expiring Within 6h',
                'value' => (string) (clone $openTradesQuery)
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now()->addHours(6))
                    ->count(),
            ],
            [
                'label' => 'Accepted This Week',
                'value' => (string) Trade::query()
                    ->where('status', TradeStatus::Accepted->value)
                    ->where(fn ($query) => $query
                        ->where('initiator_user_id', $user->id)
                        ->orWhere('receiver_user_id', $user->id))
                    ->where('updated_at', '>=', now()->subWeek())
                    ->count(),
            ],
        ];
    }

    /**
     * @return Collection<int, Trade>
     */
    public function getIncomingTrades(): Collection
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return collect();
        }

        return Trade::query()
            ->where('receiver_user_id', $user->id)
            ->where('status', TradeStatus::Open->value)
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->with(['initiator'])
            ->orderBy('expires_at')
            ->limit(6)
            ->get();
    }

    /**
     * @return Collection<int, Trade>
     */
    public function getOutgoingTrades(): Collection
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return collect();
        }

        return Trade::query()
            ->where('initiator_user_id', $user->id)
            ->where('status', TradeStatus::Open->value)
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->with(['receiver'])
            ->orderBy('expires_at')
            ->limit(6)
            ->get();
    }

    /**
     * @return array<int, array{label: string, description: string, url: string}>
     */
    public function getQuickLinks(): array
    {
        return [
            [
                'label' => 'Browse Market',
                'description' => 'Find items and prefill trade proposals instantly.',
                'url' => MarketListingResource::getUrl(),
            ],
            [
                'label' => 'Open Trades',
                'description' => 'Review and resolve every pending trade.',
                'url' => TradeResource::getUrl(),
            ],
            [
                'label' => 'Create Trade',
                'description' => 'Start a new proposal from your inventory.',
                'url' => TradeResource::getUrl('create'),
            ],
        ];
    }
}
