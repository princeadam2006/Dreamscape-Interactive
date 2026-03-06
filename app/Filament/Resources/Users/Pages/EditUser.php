<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $actor = auth()->user();
        $record = $this->record;

        if (! $actor instanceof User || ! $record instanceof User) {
            return;
        }

        AuditLog::query()->create([
            'user_id' => $actor->id,
            'action' => 'account.updated',
            'target_user_id' => $record->id,
            'target_item_id' => null,
            'meta' => [
                'roles' => $record->roles()->pluck('name')->all(),
            ],
            'created_at' => now(),
        ]);
    }
}
