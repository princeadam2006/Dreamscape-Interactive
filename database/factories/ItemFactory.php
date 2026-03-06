<?php

namespace Database\Factories;

use App\Enums\ItemRarity;
use App\Enums\ItemType;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(14),
            'type' => fake()->randomElement(ItemType::cases())->value,
            'rarity' => fake()->randomElement(ItemRarity::cases())->value,
            'required_level' => fake()->optional()->numberBetween(1, 60),
            'power' => fake()->numberBetween(0, 100),
            'speed' => fake()->numberBetween(0, 100),
            'durability' => fake()->numberBetween(0, 100),
            'magical_properties' => fake()->sentence(10),
            'tradeable_default' => fake()->boolean(85),
        ];
    }
}
