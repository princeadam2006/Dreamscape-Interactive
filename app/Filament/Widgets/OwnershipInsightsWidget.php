<?php

namespace App\Filament\Widgets;

use App\Enums\ItemType;
use App\Models\User;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class OwnershipInsightsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => \App\Models\Item::query()
                ->leftJoin('inventory_items', 'inventory_items.item_id', '=', 'items.id')
                ->select([
                    'items.id',
                    'items.name',
                    'items.type',
                    'items.rarity',
                    'items.tradeable_default',
                ])
                ->selectRaw('COUNT(DISTINCT inventory_items.user_id) AS owners_count')
                ->groupBy('items.id', 'items.name', 'items.type', 'items.rarity', 'items.tradeable_default'))
            ->columns([
                TextColumn::make('name')
                    ->label('Item')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('rarity')
                    ->badge(),
                IconColumn::make('tradeable_default')
                    ->label('Tradeable')
                    ->boolean(),
                TextColumn::make('owners_count')
                    ->label('Unique Owners')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(ItemType::options())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->where('items.type', $data['value'])
                    )),
                TernaryFilter::make('tradeable_default')
                    ->label('Tradeable')
                    ->attribute('items.tradeable_default'),
                TernaryFilter::make('locked')
                    ->label('Locked')
                    ->attribute('inventory_items.locked'),
                SelectFilter::make('window')
                    ->label('Time Window')
                    ->options([
                        '7' => 'Last 7 days',
                        '30' => 'Last 30 days',
                        '90' => 'Last 90 days',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $days = (int) ($data['value'] ?? 0);

                        return $query->when(
                            $days > 0,
                            fn (Builder $query): Builder => $query->where('inventory_items.created_at', '>=', now()->subDays($days))
                        );
                    }),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function canView(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->isAdmin()
            && auth()->user()->can('View:OwnershipInsightsWidget');
    }
}
