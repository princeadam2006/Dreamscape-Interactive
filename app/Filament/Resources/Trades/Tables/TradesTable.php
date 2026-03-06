<?php

namespace App\Filament\Resources\Trades\Tables;

use App\Enums\TradeStatus;
use App\Filament\Resources\Trades\TradeResource;
use App\Models\Trade;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class TradesTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                    ->color(fn (TradeStatus|string|null $state): string => ($state instanceof TradeStatus ? $state : TradeStatus::tryFrom((string) $state))?->color() ?? 'gray')
                    ->sortable(),
                TextColumn::make('trade_items_count')
                    ->label('Items')
                    ->counts('tradeItems'),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->since()
                    ->placeholder('No expiry')
                    ->sortable(),
                TextColumn::make('message')
                    ->limit(80)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(TradeStatus::options()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('accept')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Trade $record): bool => auth()->id() === $record->receiver_user_id && $record->status === TradeStatus::Open)
                    ->action(function (Trade $record): void {
                        try {
                            $actor = auth()->user();

                            if (! $actor instanceof User) {
                                abort(403);
                            }

                            TradeResource::acceptTrade($record, $actor);

                            Notification::make()
                                ->success()
                                ->title("Trade #{$record->id} accepted.")
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title(collect($exception->errors())->flatten()->first() ?? 'Trade could not be accepted.')
                                ->send();
                        }
                    }),
                Action::make('reject')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Trade $record): bool => auth()->id() === $record->receiver_user_id && $record->status === TradeStatus::Open)
                    ->action(function (Trade $record): void {
                        try {
                            $actor = auth()->user();

                            if (! $actor instanceof User) {
                                abort(403);
                            }

                            TradeResource::rejectTrade($record, $actor);

                            Notification::make()
                                ->success()
                                ->title("Trade #{$record->id} rejected.")
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title(collect($exception->errors())->flatten()->first() ?? 'Trade could not be rejected.')
                                ->send();
                        }
                    }),
                Action::make('cancel')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Trade $record): bool => auth()->id() === $record->initiator_user_id && $record->status === TradeStatus::Open)
                    ->action(function (Trade $record): void {
                        try {
                            $actor = auth()->user();

                            if (! $actor instanceof User) {
                                abort(403);
                            }

                            TradeResource::cancelTrade($record, $actor);

                            Notification::make()
                                ->success()
                                ->title("Trade #{$record->id} canceled.")
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title(collect($exception->errors())->flatten()->first() ?? 'Trade could not be canceled.')
                                ->send();
                        }
                    }),
                Action::make('forceCancel')
                    ->label('Force Cancel')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Trade $record): bool => auth()->user() instanceof User && auth()->user()->isAdmin() && $record->status === TradeStatus::Open)
                    ->action(function (Trade $record): void {
                        try {
                            $actor = auth()->user();

                            if (! $actor instanceof User) {
                                abort(403);
                            }

                            TradeResource::forceCancelTrade($record, $actor);

                            Notification::make()
                                ->success()
                                ->title("Trade #{$record->id} force-canceled.")
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title(collect($exception->errors())->flatten()->first() ?? 'Trade could not be force-canceled.')
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }
}
