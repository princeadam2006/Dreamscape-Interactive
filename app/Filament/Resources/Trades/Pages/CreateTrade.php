<?php

namespace App\Filament\Resources\Trades\Pages;

use App\Filament\Resources\Trades\TradeResource;
use App\Models\InventoryItem;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTrade extends CreateRecord
{
    protected static string $resource = TradeResource::class;

    public function mount(): void
    {
        parent::mount();

        $receiverId = request()->integer('receiver');
        $requestedInventoryItemId = request()->integer('requested_item');

        if ($receiverId <= 0 && $requestedInventoryItemId <= 0) {
            return;
        }

        $prefill = [];

        if ($receiverId > 0) {
            $prefill['receiver_user_id'] = $receiverId;
        }

        if ($requestedInventoryItemId > 0) {
            $requestedInventoryItem = InventoryItem::query()
                ->whereKey($requestedInventoryItemId)
                ->where('locked', false)
                ->whereRelation('item', 'tradeable_default', true)
                ->when($receiverId > 0, fn ($query) => $query->where('user_id', $receiverId))
                ->first();

            if ($requestedInventoryItem instanceof InventoryItem) {
                $prefill['receiver_user_id'] = $requestedInventoryItem->user_id;
                $prefill['requested_item_ids'] = [$requestedInventoryItem->id];
            }
        }

        if ($prefill !== []) {
            $this->form->fill([
                ...($this->data ?? []),
                ...$prefill,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return TradeResource::createTradeProposal($data, $user);
    }

    protected function getRedirectUrl(): string
    {
        return TradeResource::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Trade proposal sent.';
    }
}
