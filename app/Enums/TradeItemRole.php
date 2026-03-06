<?php

namespace App\Enums;

enum TradeItemRole: string
{
    case Offer = 'offer';
    case Request = 'request';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role): array => [$role->value => $role->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Offer => 'Offer',
            self::Request => 'Request',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Offer => 'success',
            self::Request => 'info',
        };
    }
}
