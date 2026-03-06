<?php

namespace Database\Factories;

use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trade>
 */
class TradeFactory extends Factory
{
    protected $model = Trade::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(TradeStatus::cases());

        return [
            'initiator_user_id' => User::factory(),
            'receiver_user_id' => User::factory(),
            'status' => $status->value,
            'message' => fake()->optional()->sentence(8),
            'expires_at' => $status === TradeStatus::Open
                ? now()->addHours(fake()->numberBetween(2, 72))
                : now()->subHours(fake()->numberBetween(1, 72)),
        ];
    }
}
