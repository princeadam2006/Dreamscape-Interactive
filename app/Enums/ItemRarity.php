<?php

namespace App\Enums;

enum ItemRarity: string
{
    case Common = 'common';
    case Uncommon = 'uncommon';
    case Rare = 'rare';
    case Epic = 'epic';
    case Legendary = 'legendary';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $rarity): array => [$rarity->value => $rarity->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Common => 'Common',
            self::Uncommon => 'Uncommon',
            self::Rare => 'Rare',
            self::Epic => 'Epic',
            self::Legendary => 'Legendary',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Common => 'gray',
            self::Uncommon => 'success',
            self::Rare => 'info',
            self::Epic => 'warning',
            self::Legendary => 'danger',
        };
    }
}
