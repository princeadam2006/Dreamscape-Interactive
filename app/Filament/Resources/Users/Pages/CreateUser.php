<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function afterCreate(): void
    {
        $actor = auth()->user();
        $record = $this->record;

        if (! $actor instanceof User || ! $record instanceof User) {
            return;
        }

        AuditLog::query()->create([
            'user_id' => $actor->id,
            'action' => 'account.created',
            'target_user_id' => $record->id,
            'target_item_id' => null,
            'meta' => [
                'roles' => $record->roles()->pluck('name')->all(),
            ],
            'created_at' => now(),
        ]);
    }
}
