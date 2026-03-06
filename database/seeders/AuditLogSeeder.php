<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (AuditLog::query()->exists()) {
            return;
        }

        $adminUser = User::query()->role('admin')->first() ?? User::query()->first();
        $targetUser = User::query()->role('player')->first();
        $targetItem = Item::query()->first();

        if (! $adminUser) {
            return;
        }

        AuditLog::query()->create([
            'user_id' => $adminUser->id,
            'action' => 'account.created',
            'target_user_id' => $targetUser?->id,
            'target_item_id' => null,
            'meta' => ['reason' => 'Initial bootstrap player account'],
            'created_at' => now()->subDays(2),
        ]);

        AuditLog::query()->create([
            'user_id' => $adminUser->id,
            'action' => 'item.assigned',
            'target_user_id' => $targetUser?->id,
            'target_item_id' => $targetItem?->id,
            'meta' => ['reason' => 'Compensation for lost inventory item'],
            'created_at' => now()->subDay(),
        ]);
    }
}
