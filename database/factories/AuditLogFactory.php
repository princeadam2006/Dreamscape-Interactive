<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement([
                'account.created',
                'item.assigned',
                'trade.updated',
            ]),
            'target_user_id' => fake()->boolean(50) ? User::factory() : null,
            'target_item_id' => fake()->boolean(50) ? Item::factory() : null,
            'meta' => [
                'reason' => fake()->sentence(6),
            ],
            'created_at' => fake()->dateTimeThisYear(),
        ];
    }
}
