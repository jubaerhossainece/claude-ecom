<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductVariant extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'product_id', 'sku', 'barcode', 'attribute_values', 'variant_label',
        'price', 'sale_price', 'weight', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'attribute_values' => 'array',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class, 'variant_id');
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->sale_price ?? $this->price ?? $this->product->effective_price;
    }

    public function getStockQuantityAttribute(): int
    {
        $stocks = $this->warehouseStocks()
            ->selectRaw('SUM(quantity) as quantity, SUM(reserved_quantity) as reserved_quantity')
            ->first();

        return max(0, (int) $stocks->quantity - (int) $stocks->reserved_quantity);
    }

    public function getOnHandQuantityAttribute(): int
    {
        return (int) $this->warehouseStocks()->sum('quantity');
    }

    public function getReservedQuantityAttribute(): int
    {
        return (int) $this->warehouseStocks()->sum('reserved_quantity');
    }

    public function getIsInStockAttribute(): bool
    {
        if (! $this->product->track_inventory) {
            return true;
        }
        return $this->stock_quantity > 0 || $this->product->allow_backorder;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }
}
