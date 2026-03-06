<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'notification_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (filled($user->username)) {
                return;
            }

            $base = Str::of($user->name ?: Str::before((string) $user->email, '@'))
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->limit(30, '');

            if ($base === '') {
                $base = 'player';
            }

            $candidate = $base;
            $suffix = 1;

            while (User::query()->where('username', $candidate)->exists()) {
                $candidate = "{$base}_{$suffix}";
                $suffix++;
            }

            $user->username = (string) $candidate;
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function initiatedTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'initiator_user_id');
    }

    public function receivedTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'receiver_user_id');
    }

    public function tradeItems(): HasMany
    {
        return $this->hasMany(TradeItem::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function targetedAuditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'target_user_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function isPlayer(): bool
    {
        return $this->hasRole('player');
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }

    public function canReceiveTradeUpdateEmails(): bool
    {
        return (bool) ($this->notification_preferences['email_trade_updates'] ?? false);
    }
}
