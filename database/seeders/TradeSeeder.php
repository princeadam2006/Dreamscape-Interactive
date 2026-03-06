<?php

namespace Database\Seeders;

use App\Models\Trade;
use App\Models\User;
use Illuminate\Database\Seeder;

class TradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Trade::query()->exists()) {
            return;
        }

        $players = User::query()->role('player')->limit(3)->get();

        if ($players->count() < 2) {
            return;
        }

        Trade::query()->create([
            'initiator_user_id' => $players[0]->id,
            'receiver_user_id' => $players[1]->id,
            'status' => Trade::STATUS_OPEN,
            'message' => 'Interested in a weapon for my artifact.',
        ]);

        Trade::query()->create([
            'initiator_user_id' => $players[1]->id,
            'receiver_user_id' => $players[0]->id,
            'status' => Trade::STATUS_ACCEPTED,
            'message' => 'Deal completed yesterday.',
        ]);

        if ($players->count() > 2) {
            Trade::query()->create([
                'initiator_user_id' => $players[2]->id,
                'receiver_user_id' => $players[0]->id,
                'status' => Trade::STATUS_REJECTED,
                'message' => 'Offer was too low.',
            ]);
        }
    }
}
