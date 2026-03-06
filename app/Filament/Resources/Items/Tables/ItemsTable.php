<?php

namespace App\Filament\Resources\Items\Tables;

use App\Enums\ItemRarity;
use App\Enums\ItemType;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable()
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (BackedEnum|string|null $state): string => $state instanceof BackedEnum ? (string) $state->value : (string) $state)
                    ->color(fn (ItemType|string|null $state): string => ($state instanceof ItemType ? $state : ItemType::tryFrom((string) $state))?->color() ?? 'gray')
                    ->sortable(),
                TextColumn::make('rarity')
                    ->badge()
                    ->formatStateUsing(fn (BackedEnum|string|null $state): string => $state instanceof BackedEnum ? (string) $state->value : (string) $state)
                    ->color(fn (ItemRarity|string|null $state): string => ($state instanceof ItemRarity ? $state : ItemRarity::tryFrom((string) $state))?->color() ?? 'gray')
                    ->sortable(),
                TextColumn::make('required_level')
                    ->sortable()
                    ->label('Required level'),
                TextColumn::make('power')
                    ->sortable(),
                TextColumn::make('speed')
                    ->sortable(),
                TextColumn::make('durability')
                    ->sortable(),
                IconColumn::make('tradeable_default')
                    ->boolean()
                    ->label('Tradeable')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(ItemType::options()),
                SelectFilter::make('rarity')
                    ->options(ItemRarity::options()),
                Filter::make('required_level')
                    ->schema([
                        TextInput::make('min')
                            ->numeric()
                            ->label('Min required level'),
                        TextInput::make('max')
                            ->numeric()
                            ->label('Max required level'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['min'] ?? null),
                                fn (Builder $query): Builder => $query->where('required_level', '>=', (int) $data['min']),
                            )
                            ->when(
                                filled($data['max'] ?? null),
                                fn (Builder $query): Builder => $query->where('required_level', '<=', (int) $data['max']),
                            );
                    }),
                TernaryFilter::make('tradeable_default')
                    ->label('Tradeable'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ]);
    }
}
