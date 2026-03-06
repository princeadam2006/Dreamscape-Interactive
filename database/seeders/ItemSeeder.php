<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'name' => 'Ashen Longsword',
                'description' => 'Balanced blade with ember-forged steel.',
                'type' => 'weapon',
                'rarity' => 'common',
                'required_level' => 5,
                'power' => 42,
                'speed' => 56,
                'durability' => 71,
                'magical_properties' => 'Adds minor fire damage on hit.',
                'tradeable_default' => true,
            ],
            [
                'name' => 'Moonlit Rapier',
                'description' => 'Light dueling weapon with high precision.',
                'type' => 'weapon',
                'rarity' => 'rare',
                'required_level' => 14,
                'power' => 58,
                'speed' => 87,
                'durability' => 49,
                'magical_properties' => 'Critical hits briefly increase movement speed.',
                'tradeable_default' => true,
            ],
            [
                'name' => 'Titan Bulwark',
                'description' => 'Massive shield used by the old capital guard.',
                'type' => 'armor',
                'rarity' => 'epic',
                'required_level' => 22,
                'power' => 35,
                'speed' => 12,
                'durability' => 95,
                'magical_properties' => 'Reduces incoming projectile damage.',
                'tradeable_default' => true,
            ],
            [
                'name' => 'Warden Cloak',
                'description' => 'Flexible layered cloak for roaming defenders.',
                'type' => 'armor',
                'rarity' => 'uncommon',
                'required_level' => 11,
                'power' => 24,
                'speed' => 63,
                'durability' => 54,
                'magical_properties' => 'Improves stealth in low-light zones.',
                'tradeable_default' => true,
            ],
            [
                'name' => 'Aether Prism',
                'description' => 'Focusing crystal used by arcanists.',
                'type' => 'artifact',
                'rarity' => 'legendary',
                'required_level' => 30,
                'power' => 92,
                'speed' => 41,
                'durability' => 60,
                'magical_properties' => 'Amplifies elemental ability strength.',
                'tradeable_default' => false,
            ],
            [
                'name' => 'Storm Idol',
                'description' => 'Ceremonial relic carrying static charge.',
                'type' => 'artifact',
                'rarity' => 'rare',
                'required_level' => 18,
                'power' => 67,
                'speed' => 48,
                'durability' => 58,
                'magical_properties' => 'Chance to chain lightning to nearby enemies.',
                'tradeable_default' => true,
            ],
            [
                'name' => 'Phoenix Draught',
                'description' => 'Restorative tonic brewed by alchemists.',
                'type' => 'consumable',
                'rarity' => 'uncommon',
                'required_level' => null,
                'power' => 15,
                'speed' => 0,
                'durability' => 0,
                'magical_properties' => 'Restores health over time.',
                'tradeable_default' => true,
            ],
            [
                'name' => 'Void Tonic',
                'description' => 'Forbidden vial with unstable energy.',
                'type' => 'consumable',
                'rarity' => 'epic',
                'required_level' => 20,
                'power' => 79,
                'speed' => 0,
                'durability' => 0,
                'magical_properties' => 'Boosts spell power, drains stamina.',
                'tradeable_default' => true,
            ],
            [
                'name' => 'Cartographer Ring',
                'description' => 'Engraved ring mapping unknown terrain.',
                'type' => 'trinket',
                'rarity' => 'common',
                'required_level' => 3,
                'power' => 10,
                'speed' => 25,
                'durability' => 44,
                'magical_properties' => 'Reveals nearby points of interest.',
                'tradeable_default' => true,
            ],
            [
                'name' => 'Chronicle Pendant',
                'description' => 'Pendant linked to lost guild records.',
                'type' => 'trinket',
                'rarity' => 'rare',
                'required_level' => 16,
                'power' => 44,
                'speed' => 36,
                'durability' => 63,
                'magical_properties' => 'Improves quest reward quality.',
                'tradeable_default' => true,
            ],
        ];

        foreach ($items as $item) {
            Item::query()->updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}
