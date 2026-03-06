<?php

namespace App\Models;

use App\Enums\ItemRarity;
use App\Enums\ItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    /** @use HasFactory<\Database\Factories\ItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'rarity',
        'required_level',
        'power',
        'speed',
        'durability',
        'magical_properties',
        'tradeable_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ItemType::class,
            'rarity' => ItemRarity::class,
            'required_level' => 'integer',
            'power' => 'integer',
            'speed' => 'integer',
            'durability' => 'integer',
            'tradeable_default' => 'boolean',
        ];
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'target_item_id');
    }
}
