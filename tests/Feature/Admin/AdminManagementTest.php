<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\AdminControlCenter;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_player_cannot_access_admin_user_management_resource(): void
    {
        $player = User::factory()->create();
        $player->assignRole('player');

        $response = $this->actingAs($player)->get('/users');

        $response->assertForbidden();
    }

    public function test_admin_can_create_user_with_roles_and_action_is_audited(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $playerRole = \Spatie\Permission\Models\Role::findByName('player', 'web');

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->set('data.name', 'Created By Admin')
            ->set('data.username', 'created_admin_user')
            ->set('data.email', 'created-admin-user@example.com')
            ->set('data.password', 'Password!1234')
            ->set('data.password_confirmation', 'Password!1234')
            ->set('data.roles', [$playerRole->id])
            ->call('create')
            ->assertHasNoErrors();

        $createdUser = User::query()->where('email', 'created-admin-user@example.com')->firstOrFail();

        $this->assertTrue($createdUser->hasRole('player'));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'account.created',
            'target_user_id' => $createdUser->id,
        ]);
    }

    public function test_admin_can_assign_item_to_player_and_audit_log_is_created(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $player = User::factory()->create();
        $player->assignRole('player');

        $item = Item::factory()->create();

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callTableAction('assignItem', $player->id, [
                'item_id' => $item->id,
                'locked' => false,
                'reason' => 'Season reward',
            ]);

        $this->assertDatabaseHas('inventory_items', [
            'user_id' => $player->id,
            'item_id' => $item->id,
            'locked' => false,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'item.assigned',
            'target_user_id' => $player->id,
            'target_item_id' => $item->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $player->id,
            'type' => FilamentDatabaseNotification::class,
        ]);
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
