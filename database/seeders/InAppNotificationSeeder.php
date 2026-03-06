<?php

namespace Database\Seeders;

use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InAppNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (DB::table('notifications')->exists()) {
            return;
        }

        $users = User::query()->get();

        foreach ($users as $user) {
            foreach (range(1, fake()->numberBetween(2, 4)) as $_) {
                FilamentNotification::make()
                    ->title(fake()->sentence(4))
                    ->body(fake()->sentence(12))
                    ->info()
                    ->sendToDatabase($user);
            }
        }
    }
}
