<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class Register extends BaseRegister
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getUsernameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->nullable()
            ->maxLength(255)
            ->alphaDash()
            ->unique($this->getUserModel(), 'username')
            ->helperText('Optional. If left blank, a unique username is generated automatically.')
            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $state : null)
            ->autofocus();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        $data['notification_preferences'] = [
            'trade_updates' => true,
            'admin_announcements' => true,
            'email_trade_updates' => false,
        ];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        $user = parent::handleRegistration($data);

        if ($user instanceof User && Role::query()->where('name', 'player')->where('guard_name', 'web')->exists()) {
            $user->assignRole('player');
        }

        return $user;
    }
}
