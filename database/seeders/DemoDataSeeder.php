<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@dreamscape.test'],
            [
                'name' => 'Dreamscape Admin',
                'username' => 'admin_master',
                'password' => Hash::make('Admin!234'),
                'email_verified_at' => now(),
                'notification_preferences' => [
                    'trade_updates' => true,
                    'admin_announcements' => true,
                    'email_trade_updates' => true,
                ],
            ]
        );

        $admin->syncRoles(['admin', 'super_admin']);

        $players = [
            [
                'name' => 'Ava Storm',
                'username' => 'ava_storm',
                'email' => 'ava@dreamscape.test',
            ],
            [
                'name' => 'Noah Vale',
                'username' => 'noah_vale',
                'email' => 'noah@dreamscape.test',
            ],
            [
                'name' => 'Luna Wren',
                'username' => 'luna_wren',
                'email' => 'luna@dreamscape.test',
            ],
        ];

        foreach ($players as $playerData) {
            $player = User::query()->firstOrCreate(
                ['email' => $playerData['email']],
                [
                    'name' => $playerData['name'],
                    'username' => $playerData['username'],
                    'password' => Hash::make('Player!234'),
                    'email_verified_at' => now(),
                    'notification_preferences' => [
                        'trade_updates' => true,
                        'admin_announcements' => false,
                        'email_trade_updates' => false,
                    ],
                ]
            );

            $player->syncRoles(['player']);
        }

        $this->call([
            InventoryItemSeeder::class,
            TradeSeeder::class,
            TradeItemSeeder::class,
            InAppNotificationSeeder::class,
            AuditLogSeeder::class,
        ]);
    }
}
