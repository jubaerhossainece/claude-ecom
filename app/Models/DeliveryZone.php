<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryZone extends Model
{
    protected $fillable = [
        'store_id', 'name', 'type', 'location_ids',
        'delivery_charge', 'free_delivery_above',
        'estimated_days_min', 'estimated_days_max', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'location_ids' => 'array',
        'delivery_charge' => 'decimal:2',
        'free_delivery_above' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function getChargeForOrder(float $subtotal): float
    {
        if ($this->free_delivery_above && $subtotal >= $this->free_delivery_above) {
            return 0;
        }

        return $this->delivery_charge;
    }

    public function matchesDistrict(int $districtId): bool
    {
        if ($this->type === 'nationwide') {
            return true;
        }

        return in_array($districtId, $this->location_ids ?? []);
    }
}
