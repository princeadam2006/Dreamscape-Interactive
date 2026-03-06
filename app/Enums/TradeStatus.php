<?php

namespace App\Enums;

enum TradeStatus: string
{
    case Open = 'open';
    case Expired = 'expired';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Canceled = 'canceled';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Expired => 'Expired',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Canceled => 'Canceled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'info',
            self::Accepted => 'success',
            self::Rejected, self::Canceled, self::Expired => 'warning',
        };
    }
}
