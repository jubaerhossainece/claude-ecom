<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wishlist extends Model
{
    protected $fillable = ['customer_id', 'product_id', 'variant_id', 'price_at_added'];

    protected $casts = [
        'price_at_added' => 'decimal:2',
    ];

    public function getCurrentPriceAttribute(): ?float
    {
        return $this->variant?->effective_price ?? $this->product?->effective_price;
    }

    public function getHasPriceDropAttribute(): bool
    {
        return $this->price_at_added !== null
            && $this->current_price !== null
            && $this->current_price < $this->price_at_added;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
