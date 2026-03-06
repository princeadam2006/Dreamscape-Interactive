<?php

namespace App\Enums;

enum ItemType: string
{
    case Weapon = 'weapon';
    case Armor = 'armor';
    case Artifact = 'artifact';
    case Consumable = 'consumable';
    case Trinket = 'trinket';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Weapon => 'Weapon',
            self::Armor => 'Armor',
            self::Artifact => 'Artifact',
            self::Consumable => 'Consumable',
            self::Trinket => 'Trinket',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Weapon => 'danger',
            self::Armor => 'info',
            self::Artifact => 'warning',
            self::Consumable => 'success',
            self::Trinket => 'primary',
        };
    }
}
