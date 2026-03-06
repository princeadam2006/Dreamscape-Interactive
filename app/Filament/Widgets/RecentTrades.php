<?php

namespace App\Filament\Widgets;

use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Models\User;
use BackedEnum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentTrades extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Trades';

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $user = auth()->user();

                $query = Trade::query()
                    ->with(['initiator', 'receiver'])
                    ->latest('created_at');

                if ($user instanceof User && $user->isPlayer()) {
                    $query->where(fn (Builder $query): Builder => $query
                        ->where('initiator_user_id', $user->id)
                        ->orWhere('receiver_user_id', $user->id));
                }

                return $query;
            })
            ->columns([
                TextColumn::make('id')
                    ->label('Trade #')
                    ->sortable(),
                TextColumn::make('initiator.username')
                    ->label('Initiator')
                    ->searchable(),
                TextColumn::make('receiver.username')
                    ->label('Receiver')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (BackedEnum|string|null $state): string => $state instanceof BackedEnum ? $state->label() : (string) $state)
                    ->color(fn (TradeStatus|string|null $state): string => ($state instanceof TradeStatus ? $state : TradeStatus::tryFrom((string) $state))?->color() ?? 'gray'),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->since()
                    ->placeholder('No expiry'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since(),
            ])
            ->paginated([10, 25])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
