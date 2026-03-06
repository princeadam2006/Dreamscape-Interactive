<?php

namespace Tests\Feature\Trade;

use App\Enums\TradeStatus;
use App\Filament\Resources\Trades\Pages\CreateTrade;
use App\Filament\Resources\Trades\Pages\ListTrades;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\Trade;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TradeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_player_can_create_trade_proposal_and_receiver_gets_notification(): void
    {
        $initiator = User::factory()->create();
        $receiver = User::factory()->create();
        $initiator->assignRole('player');
        $receiver->assignRole('player');

        $tradeableItem = Item::factory()->create(['tradeable_default' => true]);
        $offeredInventory = InventoryItem::factory()->create([
            'user_id' => $initiator->id,
            'item_id' => $tradeableItem->id,
            'locked' => false,
        ]);
        $requestedInventory = InventoryItem::factory()->create([
            'user_id' => $receiver->id,
            'item_id' => $tradeableItem->id,
            'locked' => false,
        ]);

        Livewire::actingAs($initiator)
            ->test(CreateTrade::class)
            ->set('data.receiver_user_id', $receiver->id)
            ->set('data.message', 'Let us trade.')
            ->set('data.offered_item_ids', [$offeredInventory->id])
            ->set('data.requested_item_ids', [$requestedInventory->id])
            ->call('create')
            ->assertHasNoErrors();

        $trade = Trade::query()->firstOrFail();

        $this->assertSame($initiator->id, $trade->initiator_user_id);
        $this->assertSame($receiver->id, $trade->receiver_user_id);
        $this->assertSame(TradeStatus::Open, $trade->status);
        $this->assertNotNull($trade->expires_at);
        $this->assertDatabaseCount('trade_items', 2);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $receiver->id,
            'type' => FilamentDatabaseNotification::class,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'id' => $offeredInventory->id,
            'locked' => true,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'id' => $requestedInventory->id,
            'locked' => true,
        ]);
    }

    public function test_trade_acceptance_is_transactional_and_transfers_item_ownership(): void
    {
        $initiator = User::factory()->create();
        $receiver = User::factory()->create();
        $initiator->assignRole('player');
        $receiver->assignRole('player');

        $item = Item::factory()->create(['tradeable_default' => true]);
        $offeredInventory = InventoryItem::factory()->create([
            'user_id' => $initiator->id,
            'item_id' => $item->id,
            'locked' => true,
        ]);
        $requestedInventory = InventoryItem::factory()->create([
            'user_id' => $receiver->id,
            'item_id' => $item->id,
            'locked' => true,
        ]);

        $trade = Trade::query()->create([
            'initiator_user_id' => $initiator->id,
            'receiver_user_id' => $receiver->id,
            'status' => TradeStatus::Open->value,
            'message' => 'Offer',
            'expires_at' => now()->addHours(24),
        ]);

        $trade->tradeItems()->createMany([
            [
                'inventory_item_id' => $offeredInventory->id,
                'user_id' => $initiator->id,
                'role_in_trade' => 'offer',
            ],
            [
                'inventory_item_id' => $requestedInventory->id,
                'user_id' => $receiver->id,
                'role_in_trade' => 'request',
            ],
        ]);

        Livewire::actingAs($receiver)
            ->test(ListTrades::class)
            ->callTableAction('accept', $trade->id);

        $trade->refresh();
        $offeredInventory->refresh();
        $requestedInventory->refresh();

        $this->assertSame(TradeStatus::Accepted, $trade->status);
        $this->assertSame($receiver->id, $offeredInventory->user_id);
        $this->assertSame($initiator->id, $requestedInventory->user_id);
        $this->assertFalse($offeredInventory->locked);
        $this->assertFalse($requestedInventory->locked);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $initiator->id,
            'type' => FilamentDatabaseNotification::class,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $receiver->id,
            'type' => FilamentDatabaseNotification::class,
        ]);
    }

    public function test_trade_creation_rejects_locked_or_unavailable_items(): void
    {
        $initiator = User::factory()->create();
        $receiver = User::factory()->create();
        $initiator->assignRole('player');
        $receiver->assignRole('player');

        $item = Item::factory()->create(['tradeable_default' => true]);
        $lockedOfferedItem = InventoryItem::factory()->create([
            'user_id' => $initiator->id,
            'item_id' => $item->id,
            'locked' => true,
        ]);
        $requestedItem = InventoryItem::factory()->create([
            'user_id' => $receiver->id,
            'item_id' => $item->id,
            'locked' => false,
        ]);

        Livewire::actingAs($initiator)
            ->test(CreateTrade::class)
            ->set('data.receiver_user_id', $receiver->id)
            ->set('data.offered_item_ids', [$lockedOfferedItem->id])
            ->set('data.requested_item_ids', [$requestedItem->id])
            ->call('create');

        $this->assertDatabaseCount('trades', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $receiver->id,
            'type' => FilamentDatabaseNotification::class,
        ]);
    }

    public function test_trade_rejection_unlocks_trade_items(): void
    {
        $initiator = User::factory()->create();
        $receiver = User::factory()->create();
        $initiator->assignRole('player');
        $receiver->assignRole('player');

        $item = Item::factory()->create(['tradeable_default' => true]);
        $offeredInventory = InventoryItem::factory()->create([
            'user_id' => $initiator->id,
            'item_id' => $item->id,
            'locked' => true,
        ]);
        $requestedInventory = InventoryItem::factory()->create([
            'user_id' => $receiver->id,
            'item_id' => $item->id,
            'locked' => true,
        ]);

        $trade = Trade::query()->create([
            'initiator_user_id' => $initiator->id,
            'receiver_user_id' => $receiver->id,
            'status' => TradeStatus::Open->value,
            'message' => 'Offer',
            'expires_at' => now()->addHours(24),
        ]);

        $trade->tradeItems()->createMany([
            [
                'inventory_item_id' => $offeredInventory->id,
                'user_id' => $initiator->id,
                'role_in_trade' => 'offer',
            ],
            [
                'inventory_item_id' => $requestedInventory->id,
                'user_id' => $receiver->id,
                'role_in_trade' => 'request',
            ],
        ]);

        Livewire::actingAs($receiver)
            ->test(ListTrades::class)
            ->callTableAction('reject', $trade->id);

        $trade->refresh();
        $offeredInventory->refresh();
        $requestedInventory->refresh();

        $this->assertSame(TradeStatus::Rejected, $trade->status);
        $this->assertFalse($offeredInventory->locked);
        $this->assertFalse($requestedInventory->locked);
    }

    public function test_admin_can_force_cancel_open_trade(): void
    {
        $admin = User::factory()->create();
        $initiator = User::factory()->create();
        $receiver = User::factory()->create();
        $admin->assignRole('admin');
        $initiator->assignRole('player');
        $receiver->assignRole('player');

        $item = Item::factory()->create(['tradeable_default' => true]);
        $offeredInventory = InventoryItem::factory()->create([
            'user_id' => $initiator->id,
            'item_id' => $item->id,
            'locked' => true,
        ]);
        $requestedInventory = InventoryItem::factory()->create([
            'user_id' => $receiver->id,
            'item_id' => $item->id,
            'locked' => true,
        ]);

        $trade = Trade::query()->create([
            'initiator_user_id' => $initiator->id,
            'receiver_user_id' => $receiver->id,
            'status' => TradeStatus::Open->value,
            'message' => 'Offer',
            'expires_at' => now()->addHours(24),
        ]);

        $trade->tradeItems()->createMany([
            [
                'inventory_item_id' => $offeredInventory->id,
                'user_id' => $initiator->id,
                'role_in_trade' => 'offer',
            ],
            [
                'inventory_item_id' => $requestedInventory->id,
                'user_id' => $receiver->id,
                'role_in_trade' => 'request',
            ],
        ]);

        Livewire::actingAs($admin)
            ->test(ListTrades::class)
            ->callTableAction('forceCancel', $trade->id);

        $trade->refresh();
        $offeredInventory->refresh();
        $requestedInventory->refresh();

        $this->assertSame(TradeStatus::Canceled, $trade->status);
        $this->assertFalse($offeredInventory->locked);
        $this->assertFalse($requestedInventory->locked);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'trade.force_canceled',
        ]);
    }
}
