<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use Notifiable;

    public const VIP_SPEND_THRESHOLD = 20000;

    public const NEW_DAYS = 30;

    public const INACTIVE_DAYS = 90;

    public const SEGMENTS = [
        'vip' => 'VIP',
        'new' => 'New',
        'returning' => 'Returning',
        'inactive' => 'Inactive',
        'active' => 'Active',
    ];

    protected $fillable = [
        'store_id', 'name', 'phone', 'phone_verified_at',
        'email', 'password', 'is_active', 'meta', 'admin_notes',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'meta' => 'array',
        'password' => 'hashed',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress()
    {
        return $this->addresses()->where('is_default', true)->first()
            ?? $this->addresses()->latest()->first();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function getLifetimeSpendAttribute(): float
    {
        return (float) ($this->orders_sum_total ?? $this->orders()->sum('total'));
    }

    public function getSegmentAttribute(): string
    {
        if ($this->lifetime_spend >= self::VIP_SPEND_THRESHOLD) {
            return 'vip';
        }

        if ($this->created_at && $this->created_at->diffInDays(now()) <= self::NEW_DAYS) {
            return 'new';
        }

        $lastOrderAt = $this->orders_max_created_at ?? $this->orders()->max('created_at');

        if ($lastOrderAt && now()->diffInDays($lastOrderAt) > self::INACTIVE_DAYS) {
            return 'inactive';
        }

        $ordersCount = (int) ($this->orders_count ?? $this->orders()->count());

        if ($ordersCount > 1) {
            return 'returning';
        }

        return 'active';
    }
}
