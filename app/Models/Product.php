<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, Searchable;

    protected $fillable = [
        'store_id', 'category_id', 'brand_id', 'name', 'slug', 'short_description', 'description',
        'status', 'is_featured', 'unit_of_sale', 'base_price', 'sale_price', 'cost_price',
        'sku', 'barcode', 'low_stock_threshold', 'track_inventory',
        'allow_backorder', 'weight', 'dimensions', 'sort_order', 'meta_title', 'meta_description', 'meta',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'track_inventory' => 'boolean',
        'allow_backorder' => 'boolean',
        'base_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'dimensions' => 'array',
        'meta' => 'array',
    ];

    public const STATUSES = ['draft', 'active', 'inactive', 'archived'];
    public const UNITS = ['piece', 'kg', 'gram', 'litre', 'ml', 'pack', 'dozen', 'bundle', 'set', 'pair'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
        $this->addMediaCollection('thumbnail')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)->height(300)->performOnCollections('images', 'thumbnail');
        $this->addMediaConversion('medium')
            ->width(600)->height(600)->performOnCollections('images');
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->sale_price ?? $this->base_price;
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->base_price;
    }

    public function getStockQuantityAttribute(): int
    {
        if ($this->variants()->exists()) {
            $stocks = WarehouseStock::whereIn('variant_id', $this->variants()->pluck('id'))
                ->selectRaw('SUM(quantity) as quantity, SUM(reserved_quantity) as reserved_quantity')
                ->first();
        } else {
            $stocks = $this->warehouseStocks()->whereNull('variant_id')
                ->selectRaw('SUM(quantity) as quantity, SUM(reserved_quantity) as reserved_quantity')
                ->first();
        }

        return max(0, (int) $stocks->quantity - (int) $stocks->reserved_quantity);
    }

    public function getOnHandQuantityAttribute(): int
    {
        if ($this->variants()->exists()) {
            return (int) WarehouseStock::whereIn('variant_id', $this->variants()->pluck('id'))->sum('quantity');
        }

        return (int) $this->warehouseStocks()->whereNull('variant_id')->sum('quantity');
    }

    public function getReservedQuantityAttribute(): int
    {
        if ($this->variants()->exists()) {
            return (int) WarehouseStock::whereIn('variant_id', $this->variants()->pluck('id'))->sum('reserved_quantity');
        }

        return (int) $this->warehouseStocks()->whereNull('variant_id')->sum('reserved_quantity');
    }

    public function getIsInStockAttribute(): bool
    {
        if (! $this->track_inventory) {
            return true;
        }
        return $this->stock_quantity > 0 || $this->allow_backorder;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->track_inventory && $this->stock_quantity <= $this->low_stock_threshold && $this->stock_quantity > 0;
    }

    public function getThumbnailUrlAttribute(): string
    {
        $media = $this->getFirstMedia('thumbnail') ?? $this->getFirstMedia('images');
        return $media ? $media->getUrl('thumb') : asset('images/placeholder.png');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->short_description,
            'category' => $this->category?->name,
            'sku' => $this->sku,
            'base_price' => $this->base_price,
            'status' => $this->status,
            'store_id' => $this->store_id,
        ];
    }
}
