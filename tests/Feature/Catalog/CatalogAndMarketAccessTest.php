<?php

namespace Tests\Feature\Catalog;

use App\Filament\Resources\Items\Pages\ListItems;
use App\Filament\Resources\MarketListings\Pages\ManageMarketListings;
use App\Filament\Resources\Trades\Pages\CreateTrade;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogAndMarketAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_player_sees_only_tradeable_items_in_catalog_but_admin_sees_all_items(): void
    {
        $player = User::factory()->create();
        $admin = User::factory()->create();
        $player->assignRole('player');
        $admin->assignRole('admin');

        $tradeableItem = Item::factory()->create(['tradeable_default' => true]);
        $nonTradeableItem = Item::factory()->create(['tradeable_default' => false]);

        Livewire::actingAs($player)
            ->test(ListItems::class)
            ->assertCanSeeTableRecords([$tradeableItem])
            ->assertCanNotSeeTableRecords([$nonTradeableItem]);

        Livewire::actingAs($admin)
            ->test(ListItems::class)
            ->assertCanSeeTableRecords([$tradeableItem, $nonTradeableItem]);
    }

    public function test_market_lists_only_tradeable_inventory_and_disables_locked_conflicting_actions(): void
    {
        $player = User::factory()->create();
        $otherPlayer = User::factory()->create();
        $player->assignRole('player');
        $otherPlayer->assignRole('player');

        $tradeableItem = Item::factory()->create(['tradeable_default' => true]);
        $nonTradeableItem = Item::factory()->create(['tradeable_default' => false]);

        $availableMarketRecord = InventoryItem::factory()->create([
            'user_id' => $otherPlayer->id,
            'item_id' => $tradeableItem->id,
            'locked' => false,
        ]);
        $lockedMarketRecord = InventoryItem::factory()->create([
            'user_id' => $otherPlayer->id,
            'item_id' => $tradeableItem->id,
            'locked' => true,
        ]);
        $nonTradeableMarketRecord = InventoryItem::factory()->create([
            'user_id' => $otherPlayer->id,
            'item_id' => $nonTradeableItem->id,
            'locked' => false,
        ]);
        InventoryItem::factory()->create([
            'user_id' => $player->id,
            'item_id' => $tradeableItem->id,
            'locked' => false,
        ]);

        Livewire::actingAs($player)
            ->test(ManageMarketListings::class)
            ->assertCanSeeTableRecords([$availableMarketRecord, $lockedMarketRecord])
            ->assertCanNotSeeTableRecords([$nonTradeableMarketRecord])
            ->assertTableActionEnabled('proposeTrade', $availableMarketRecord->id)
            ->assertTableActionDisabled('proposeTrade', $lockedMarketRecord->id);
    }

    public function test_admin_cannot_access_player_market_resource(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/market');

        $response->assertForbidden();
    }

    public function test_market_trade_link_prefills_trade_create_form(): void
    {
        $player = User::factory()->create();
        $otherPlayer = User::factory()->create();
        $player->assignRole('player');
        $otherPlayer->assignRole('player');

        $tradeableItem = Item::factory()->create(['tradeable_default' => true]);
        $marketRecord = InventoryItem::factory()->create([
            'user_id' => $otherPlayer->id,
            'item_id' => $tradeableItem->id,
            'locked' => false,
        ]);

        Livewire::withQueryParams([
            'receiver' => $otherPlayer->id,
            'requested_item' => $marketRecord->id,
        ])
            ->actingAs($player)
            ->test(CreateTrade::class)
            ->assertSet('data.receiver_user_id', $otherPlayer->id)
            ->assertSet('data.requested_item_ids', [$marketRecord->id]);
    }
}
