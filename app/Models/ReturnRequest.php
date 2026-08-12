<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ReturnRequest extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const TYPES = [
        'return' => 'Return',
        'exchange' => 'Exchange',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
    ];

    public const REASONS = [
        'defective' => 'Defective / damaged item',
        'wrong_item' => 'Wrong item received',
        'not_as_described' => 'Not as described',
        'size_fit' => 'Size / fit issue',
        'no_longer_needed' => 'No longer needed',
        'other' => 'Other',
    ];

    protected $fillable = [
        'store_id', 'order_id', 'order_item_id', 'customer_id',
        'type', 'reason', 'description', 'status', 'admin_notes',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
    }
}
