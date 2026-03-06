<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getUsernameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPreferencesSection(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->required()
            ->maxLength(255)
            ->alphaDash()
            ->unique(ignoreRecord: true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $defaults = [
            'trade_updates' => true,
            'admin_announcements' => true,
            'email_trade_updates' => false,
        ];

        $data['notification_preferences'] = array_merge(
            $defaults,
            is_array($data['notification_preferences'] ?? null) ? $data['notification_preferences'] : []
        );

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $preferences = $data['notification_preferences'] ?? [];

        $data['notification_preferences'] = [
            'trade_updates' => (bool) ($preferences['trade_updates'] ?? false),
            'admin_announcements' => (bool) ($preferences['admin_announcements'] ?? false),
            'email_trade_updates' => (bool) ($preferences['email_trade_updates'] ?? false),
        ];

        return $data;
    }

    protected function getPreferencesSection(): Section
    {
        return Section::make('Notification Preferences')
            ->schema([
                Toggle::make('notification_preferences.trade_updates')
                    ->label('Trade updates')
                    ->inline(false),
                Toggle::make('notification_preferences.admin_announcements')
                    ->label('Admin announcements')
                    ->inline(false),
                Toggle::make('notification_preferences.email_trade_updates')
                    ->label('Email trade updates')
                    ->inline(false),
            ]);
    }
}
