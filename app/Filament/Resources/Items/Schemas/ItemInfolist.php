<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Enums\ItemRarity;
use App\Enums\ItemType;
use BackedEnum;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Overview')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('type')
                            ->badge()
                            ->formatStateUsing(fn (BackedEnum|string|null $state): string => $state instanceof BackedEnum ? (string) $state->value : (string) $state)
                            ->color(fn (ItemType|string|null $state): string => ($state instanceof ItemType ? $state : ItemType::tryFrom((string) $state))?->color() ?? 'gray'),
                        TextEntry::make('rarity')
                            ->badge()
                            ->formatStateUsing(fn (BackedEnum|string|null $state): string => $state instanceof BackedEnum ? (string) $state->value : (string) $state)
                            ->color(fn (ItemRarity|string|null $state): string => ($state instanceof ItemRarity ? $state : ItemRarity::tryFrom((string) $state))?->color() ?? 'gray'),
                        TextEntry::make('required_level')
                            ->placeholder('None'),
                        IconEntry::make('tradeable_default')
                            ->label('Tradeable by default')
                            ->boolean(),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Stats')
                    ->schema([
                        TextEntry::make('power'),
                        TextEntry::make('speed'),
                        TextEntry::make('durability'),
                        TextEntry::make('magical_properties')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }
}
