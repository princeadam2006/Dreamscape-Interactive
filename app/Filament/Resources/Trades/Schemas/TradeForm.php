<?php

namespace App\Filament\Resources\Trades\Schemas;

use App\Filament\Resources\Trades\TradeResource;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TradeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Trade Proposal')
                    ->schema([
                        Select::make('receiver_user_id')
                            ->label('Trade With')
                            ->options(fn (): array => User::query()
                                ->where('id', '!=', auth()->id())
                                ->whereHas('roles', fn (Builder $query): Builder => $query->where('name', 'player'))
                                ->orderBy('username')
                                ->pluck('username', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->helperText('Only players are listed.')
                            ->afterStateUpdated(function (Set $set): void {
                                $set('requested_item_ids', []);
                            })
                            ->live(),
                        Textarea::make('message')
                            ->placeholder('Add context for the trade...')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Items')
                    ->schema([
                        Select::make('offered_item_ids')
                            ->label('Items You Offer')
                            ->options(fn (): array => TradeResource::availableInventoryItemOptionsForUser(auth()->id()))
                            ->searchable()
                            ->multiple()
                            ->helperText('Only unlocked and tradeable items are shown.')
                            ->required(),
                        Select::make('requested_item_ids')
                            ->label('Items You Request')
                            ->options(fn (Get $get): array => TradeResource::availableInventoryItemOptionsForUser((int) ($get('receiver_user_id') ?? 0)))
                            ->searchable()
                            ->multiple()
                            ->helperText('Pick the items you want from the selected player.')
                            ->disabled(fn (Get $get): bool => ! filled($get('receiver_user_id')))
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
