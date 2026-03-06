<?php

namespace App\Models;

use App\Enums\TradeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trade extends Model
{
    /** @use HasFactory<\Database\Factories\TradeFactory> */
    use HasFactory;

    public const STATUS_OPEN = TradeStatus::Open->value;

    public const STATUS_EXPIRED = TradeStatus::Expired->value;

    public const STATUS_ACCEPTED = TradeStatus::Accepted->value;

    public const STATUS_REJECTED = TradeStatus::Rejected->value;

    public const STATUS_CANCELED = TradeStatus::Canceled->value;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'initiator_user_id',
        'receiver_user_id',
        'status',
        'message',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TradeStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_user_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }

    public function tradeItems(): HasMany
    {
        return $this->hasMany(TradeItem::class);
    }
}
