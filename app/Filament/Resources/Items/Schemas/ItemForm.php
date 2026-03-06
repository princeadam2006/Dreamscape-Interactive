<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Enums\ItemRarity;
use App\Enums\ItemType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Item Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        Select::make('type')
                            ->options(ItemType::options())
                            ->required(),
                        Select::make('rarity')
                            ->options(ItemRarity::options())
                            ->required(),
                        TextInput::make('required_level')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->nullable(),
                        Toggle::make('tradeable_default')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Item Stats')
                    ->schema([
                        TextInput::make('power')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100),
                        TextInput::make('speed')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100),
                        TextInput::make('durability')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100),
                        Textarea::make('magical_properties')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }
}
