<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;

class InventoryItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (InventoryItem::query()->exists()) {
            return;
        }

        $players = User::query()->role('player')->get();
        $itemIds = Item::query()->pluck('id');

        if ($players->isEmpty() || $itemIds->isEmpty()) {
            return;
        }

        foreach ($players as $player) {
            $itemsToAssign = fake()->numberBetween(4, 8);

            for ($index = 1; $index <= $itemsToAssign; $index++) {
                InventoryItem::query()->create([
                    'user_id' => $player->id,
                    'item_id' => $itemIds->random(),
                    'locked' => fake()->boolean(20),
                ]);
            }
        }
    }
}
