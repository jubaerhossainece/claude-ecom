<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'store_id', 'warehouse_id', 'product_id', 'variant_id', 'order_id',
        'type', 'quantity_change', 'quantity_after', 'reason', 'created_by',
    ];

    public const TYPES = [
        'sale' => 'Sale',
        'restock' => 'Restock',
        'adjustment' => 'Manual Adjustment',
        'return' => 'Return',
        'transfer_in' => 'Transfer In',
        'transfer_out' => 'Transfer Out',
        'damaged' => 'Damaged',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
