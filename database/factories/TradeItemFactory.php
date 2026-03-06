<?php

namespace Database\Factories;

use App\Enums\TradeItemRole;
use App\Models\InventoryItem;
use App\Models\Trade;
use App\Models\TradeItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TradeItem>
 */
class TradeItemFactory extends Factory
{
    protected $model = TradeItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trade_id' => Trade::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'user_id' => User::factory(),
            'role_in_trade' => fake()->randomElement(TradeItemRole::cases())->value,
        ];
    }
}
