<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    protected $fillable = [
        'store_id', 'code', 'description', 'type', 'value',
        'min_order_amount', 'max_discount_amount',
        'usage_limit', 'usage_limit_per_customer', 'used_count',
        'starts_at', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function isValid(float $subtotal, ?int $customerId = null): bool
    {
        if (! $this->is_active) return false;
        if ($this->starts_at && now()->lt($this->starts_at)) return false;
        if ($this->expires_at && now()->gt($this->expires_at)) return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;
        if ($this->min_order_amount && $subtotal < $this->min_order_amount) return false;

        if ($this->usage_limit_per_customer && $customerId) {
            $customerUses = Order::where('customer_id', $customerId)
                ->where('coupon_id', $this->id)
                ->count();

            if ($customerUses >= $this->usage_limit_per_customer) return false;
        }

        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        $discount = $this->type === 'percentage'
            ? $subtotal * ($this->value / 100)
            : $this->value;

        if ($this->max_discount_amount) {
            $discount = min($discount, $this->max_discount_amount);
        }
        return round(min($discount, $subtotal), 2);
    }
}
