<?php

namespace Tests\Feature\Inventory;

use App\Filament\Resources\InventoryItems\Pages\ManageInventoryItems;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\Trade;
use App\Models\TradeItem;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PersonalInventoryResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_player_only_sees_owned_inventory_records(): void
    {
        $player = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $player->assignRole('player');

        $otherPlayer = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $otherPlayer->assignRole('player');

        $ownedRecord = InventoryItem::factory()->create([
            'user_id' => $player->id,
        ]);

        $otherRecord = InventoryItem::factory()->create([
            'user_id' => $otherPlayer->id,
        ]);

        Livewire::actingAs($player)
            ->test(ManageInventoryItems::class)
            ->assertCanSeeTableRecords([$ownedRecord])
            ->assertCanNotSeeTableRecords([$otherRecord]);
    }

    public function test_player_can_search_filter_and_sort_inventory_items(): void
    {
        $player = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $player->assignRole('player');

        $lowPowerItem = Item::factory()->create([
            'name' => 'Ancient Wand',
            'type' => 'artifact',
            'rarity' => 'rare',
            'power' => 20,
            'speed' => 10,
            'durability' => 70,
        ]);

        $highPowerItem = Item::factory()->create([
            'name' => 'Solar Blade',
            'type' => 'weapon',
            'rarity' => 'epic',
            'power' => 90,
            'speed' => 45,
            'durability' => 65,
        ]);

        $lowPowerRecord = InventoryItem::factory()->create([
            'user_id' => $player->id,
            'item_id' => $lowPowerItem->id,
            'locked' => false,
        ]);

        $highPowerRecord = InventoryItem::factory()->create([
            'user_id' => $player->id,
            'item_id' => $highPowerItem->id,
            'locked' => true,
        ]);

        Livewire::actingAs($player)
            ->test(ManageInventoryItems::class)
            ->searchTable('Solar Blade')
            ->assertCanSeeTableRecords([$highPowerRecord])
            ->assertCanNotSeeTableRecords([$lowPowerRecord]);

        Livewire::actingAs($player)
            ->test(ManageInventoryItems::class)
            ->filterTable('locked', true)
            ->assertCanSeeTableRecords([$highPowerRecord])
            ->assertCanNotSeeTableRecords([$lowPowerRecord]);

        Livewire::actingAs($player)
            ->test(ManageInventoryItems::class)
            ->filterTable('rarity', 'epic')
            ->assertCanSeeTableRecords([$highPowerRecord])
            ->assertCanNotSeeTableRecords([$lowPowerRecord]);

        Livewire::actingAs($player)
            ->test(ManageInventoryItems::class)
            ->sortTable('item.power', 'desc')
            ->assertCanSeeTableRecords([$highPowerRecord, $lowPowerRecord], inOrder: true);
    }

    public function test_player_can_drop_unlocked_inventory_item_that_is_not_in_trade(): void
    {
        $player = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $player->assignRole('player');

        $record = InventoryItem::factory()->create([
            'user_id' => $player->id,
            'locked' => false,
        ]);

        Livewire::actingAs($player)
            ->test(ManageInventoryItems::class)
            ->assertTableActionEnabled('drop', $record->getKey())
            ->callTableAction('drop', $record->getKey());

        $this->assertDatabaseMissing('inventory_items', [
            'id' => $record->id,
        ]);
    }

    public function test_player_cannot_drop_locked_inventory_item(): void
    {
        $player = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $player->assignRole('player');

        $record = InventoryItem::factory()->create([
            'user_id' => $player->id,
            'locked' => true,
        ]);

        Livewire::actingAs($player)
            ->test(ManageInventoryItems::class)
            ->assertTableActionDisabled('drop', $record->getKey())
            ->callTableAction('drop', $record->getKey());

        $this->assertDatabaseHas('inventory_items', [
            'id' => $record->id,
        ]);
    }

    public function test_player_cannot_drop_inventory_item_that_is_part_of_a_trade(): void
    {
        $player = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $player->assignRole('player');

        $counterparty = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $counterparty->assignRole('player');

        $record = InventoryItem::factory()->create([
            'user_id' => $player->id,
            'locked' => false,
        ]);

        $trade = Trade::factory()->create([
            'initiator_user_id' => $player->id,
            'receiver_user_id' => $counterparty->id,
            'status' => Trade::STATUS_OPEN,
        ]);

        TradeItem::factory()->create([
            'trade_id' => $trade->id,
            'inventory_item_id' => $record->id,
            'user_id' => $player->id,
            'role_in_trade' => 'offer',
        ]);

        Livewire::actingAs($player)
            ->test(ManageInventoryItems::class)
            ->assertTableActionDisabled('drop', $record->getKey())
            ->callTableAction('drop', $record->getKey());

        $this->assertDatabaseHas('inventory_items', [
            'id' => $record->id,
        ]);
    }

    public function test_player_can_drop_item_that_is_only_in_closed_trade_history(): void
    {
        $player = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $player->assignRole('player');

        $counterparty = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $counterparty->assignRole('player');

        $record = InventoryItem::factory()->create([
            'user_id' => $player->id,
            'locked' => false,
        ]);

        $trade = Trade::factory()->create([
            'initiator_user_id' => $player->id,
            'receiver_user_id' => $counterparty->id,
            'status' => Trade::STATUS_ACCEPTED,
        ]);

        TradeItem::factory()->create([
            'trade_id' => $trade->id,
            'inventory_item_id' => $record->id,
            'user_id' => $player->id,
            'role_in_trade' => 'offer',
        ]);

        Livewire::actingAs($player)
            ->test(ManageInventoryItems::class)
            ->assertTableActionEnabled('drop', $record->getKey())
            ->callTableAction('drop', $record->getKey());

        $this->assertDatabaseMissing('inventory_items', [
            'id' => $record->id,
        ]);
    }
}
