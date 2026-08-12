<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = ['store_id', 'name', 'address', 'is_default', 'is_active'];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function (Warehouse $warehouse) {
            if ($warehouse->is_default) {
                static::where('store_id', $warehouse->store_id)
                    ->where('id', '!=', $warehouse->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
