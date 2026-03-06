<?php

namespace App\Enums;

enum NotificationType: string
{
    case TradeCreated = 'trade.created';
    case TradeExpired = 'trade.expired';
    case TradeAccepted = 'trade.accepted';
    case TradeRejected = 'trade.rejected';
    case TradeCanceled = 'trade.canceled';
    case InventoryItemAssigned = 'inventory.item_assigned';

    public function label(): string
    {
        return match ($this) {
            self::TradeCreated => 'Trade Created',
            self::TradeExpired => 'Trade Expired',
            self::TradeAccepted => 'Trade Accepted',
            self::TradeRejected => 'Trade Rejected',
            self::TradeCanceled => 'Trade Canceled',
            self::InventoryItemAssigned => 'Inventory Item Assigned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TradeAccepted, self::InventoryItemAssigned => 'success',
            self::TradeRejected, self::TradeCanceled, self::TradeExpired => 'warning',
            self::TradeCreated => 'info',
        };
    }
}
