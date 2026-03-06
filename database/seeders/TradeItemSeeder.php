<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\Trade;
use App\Models\TradeItem;
use Illuminate\Database\Seeder;

class TradeItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (TradeItem::query()->exists()) {
            return;
        }

        $trades = Trade::query()->with(['initiator', 'receiver'])->get();

        foreach ($trades as $trade) {
            $initiatorItem = InventoryItem::query()
                ->where('user_id', $trade->initiator_user_id)
                ->where('locked', false)
                ->inRandomOrder()
                ->first();

            $receiverItem = InventoryItem::query()
                ->where('user_id', $trade->receiver_user_id)
                ->where('locked', false)
                ->inRandomOrder()
                ->first();

            if (! $initiatorItem || ! $receiverItem) {
                continue;
            }

            TradeItem::query()->create([
                'trade_id' => $trade->id,
                'inventory_item_id' => $initiatorItem->id,
                'user_id' => $trade->initiator_user_id,
                'role_in_trade' => 'offer',
            ]);

            TradeItem::query()->create([
                'trade_id' => $trade->id,
                'inventory_item_id' => $receiverItem->id,
                'user_id' => $trade->receiver_user_id,
                'role_in_trade' => 'request',
            ]);
        }
    }
}
