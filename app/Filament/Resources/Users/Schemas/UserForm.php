<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('username')
                            ->required()
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->rule(Password::defaults())
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->confirmed(),
                        TextInput::make('password_confirmation')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->required(fn (string $operation): bool => $operation === 'create'),
                    ])
                    ->columns(2),
                Section::make('Roles & Preferences')
                    ->schema([
                        Select::make('roles')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('guard_name', 'web')
                                    ->orderBy('name')
                            )
                            ->multiple()
                            ->required()
                            ->preload()
                            ->searchable(),
                        Toggle::make('notification_preferences.trade_updates')
                            ->label('Trade updates')
                            ->default(true),
                        Toggle::make('notification_preferences.admin_announcements')
                            ->label('Admin announcements')
                            ->default(true),
                        Toggle::make('notification_preferences.email_trade_updates')
                            ->label('Email trade updates')
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }
}
