<?php

namespace App\Models;

use App\Services\OrderService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    protected $fillable = [
        'store_id', 'customer_id', 'coupon_id', 'order_number', 'status',
        'payment_method', 'payment_status', 'payment_reference',
        'subtotal', 'discount_amount', 'delivery_charge', 'tax_amount', 'total', 'refunded_amount',
        'customer_name', 'customer_phone', 'customer_email',
        'division_name', 'district_name', 'thana_name', 'area', 'address_line',
        'division_id', 'district_id', 'thana_id',
        'customer_notes', 'admin_notes',
        'cod_confirmed_at', 'cod_confirmed_by',
        'courier_name', 'courier_tracking_id', 'shipped_at', 'delivered_at', 'meta',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'cod_confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'meta' => 'array',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'returned' => 'Returned',
    ];

    public const PAYMENT_METHODS = [
        'cod' => 'Cash on Delivery',
        'sslcommerz' => 'SSLCommerz',
        'bkash' => 'bKash',
    ];

    public const PAYMENT_STATUSES = [
        'unpaid' => 'Unpaid',
        'paid' => 'Paid',
        'refunded' => 'Refunded',
        'failed' => 'Failed',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function thana(): BelongsTo
    {
        return $this->belongsTo(Thana::class);
    }

    public function addStatusHistory(string $status, string $note = '', string $createdBy = ''): void
    {
        $this->statusHistories()->create([
            'status' => $status,
            'note' => $note,
            'created_by' => $createdBy,
        ]);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'confirmed' => 'info',
            'processing' => 'info',
            'shipped' => 'primary',
            'delivered' => 'success',
            'cancelled', 'returned' => 'danger',
            default => 'gray',
        };
    }

    public const TERMINAL_STATUSES = ['cancelled', 'returned'];

    public function getRefundableAmountAttribute(): float
    {
        return max(0, (float) $this->total - (float) $this->refunded_amount);
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($order) {
            if (! $order->order_number) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });

        static::updating(function (Order $order) {
            if (
                $order->isDirty('status')
                && $order->status === 'shipped'
                && $order->getOriginal('status') !== 'shipped'
            ) {
                if (! $order->shipped_at) {
                    $order->shipped_at = now();
                }
                DB::transaction(fn () => app(OrderService::class)->commitStockForShippedOrder($order));
            }

            if (
                $order->isDirty('status')
                && in_array($order->status, self::TERMINAL_STATUSES, true)
                && ! in_array($order->getOriginal('status'), self::TERMINAL_STATUSES, true)
            ) {
                DB::transaction(fn () => app(OrderService::class)->restoreStockForOrder($order));
            }
        });

        static::updated(function (Order $order) {
            if ($order->wasChanged('status') && $order->customer) {
                $order->customer->notify(new \App\Notifications\OrderStatusUpdated($order, $order->status));
            }
        });
    }
}
