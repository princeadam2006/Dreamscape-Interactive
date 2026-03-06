<?php

namespace Tests\Feature\Pages;

use App\Filament\Pages\AdminControlCenter;
use App\Filament\Pages\TradeOperations;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomPagesAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_player_can_access_trade_operations_page(): void
    {
        $player = User::factory()->create();
        $player->assignRole('player');

        $response = $this->actingAs($player)->get(TradeOperations::getUrl());

        $response->assertOk();
    }

    public function test_admin_cannot_access_trade_operations_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(TradeOperations::getUrl());

        $response->assertForbidden();
    }

    public function test_admin_can_access_control_center_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(AdminControlCenter::getUrl());

        $response->assertOk();
    }

    public function test_player_cannot_access_control_center_page(): void
    {
        $player = User::factory()->create();
        $player->assignRole('player');

        $response = $this->actingAs($player)->get(AdminControlCenter::getUrl());

        $response->assertForbidden();
    }
}
