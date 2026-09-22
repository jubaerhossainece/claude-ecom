<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\DeliveryZone;
use App\Models\District;
use App\Models\Division;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Store;
use App\Models\Thana;
use App\Models\WarehouseStock;
use App\Notifications\OrderPlaced;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createFromCart(Cart $cart, array $checkoutData): Order
    {
        return DB::transaction(function () use ($cart, $checkoutData) {
            $store = Store::current();
            $cart->load('items.product', 'items.variant');
            $warehouse = $store->defaultWarehouse();

            foreach ($cart->items as $item) {
                if (! $item->product->track_inventory || $item->product->allow_backorder) {
                    continue;
                }

                $stockRow = $warehouse
                    ? WarehouseStock::where('warehouse_id', $warehouse->id)
                        ->where('product_id', $item->product_id)
                        ->where('variant_id', $item->variant_id)
                        ->first()
                    : null;

                $available = $stockRow ? ($stockRow->quantity - $stockRow->reserved_quantity) : 0;

                if ($available < $item->quantity) {
                    $name = $item->variant ? "{$item->product->name} ({$item->variant->variant_label})" : $item->product->name;
                    throw new InsufficientStockException("Not enough stock for \"{$name}\" — only {$available} available.");
                }
            }

            $subtotal = $cart->subtotal;
            $discountAmount = 0;
            $couponId = null;

            if (! empty($checkoutData['coupon_code'])) {
                $coupon = Coupon::where('code', $checkoutData['coupon_code'])
                    ->where('store_id', $store->id)
                    ->first();

                if ($coupon && $coupon->isValid($subtotal, $checkoutData['customer_id'] ?? null)) {
                    $discountAmount = $coupon->calculateDiscount($subtotal);
                    $couponId = $coupon->id;
                    $coupon->increment('used_count');
                }
            }

            $deliveryCharge = $this->calculateDelivery($checkoutData['district_id'], $subtotal, $store);

            $taxAmount = 0;
            if ($store->getSetting('tax_enabled', false)) {
                $taxRate = (float) $store->getSetting('tax_rate_percent', 0);
                $taxAmount = round(max(0, $subtotal - $discountAmount) * $taxRate / 100, 2);
            }

            $total = max(0, $subtotal - $discountAmount + $deliveryCharge + $taxAmount);

            $division = Division::find($checkoutData['division_id']);
            $district = District::find($checkoutData['district_id']);
            $thana = Thana::find($checkoutData['thana_id']);

            $order = Order::create([
                'store_id' => $store->id,
                'customer_id' => $checkoutData['customer_id'] ?? null,
                'coupon_id' => $couponId,
                'status' => 'pending',
                'payment_method' => $checkoutData['payment_method'] ?? 'cod',
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'delivery_charge' => $deliveryCharge,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'customer_name' => $checkoutData['name'],
                'customer_phone' => $checkoutData['phone'],
                'customer_email' => $checkoutData['email'] ?? null,
                'division_id' => $division?->id,
                'district_id' => $district?->id,
                'thana_id' => $thana?->id,
                'division_name' => $division?->name,
                'district_name' => $district?->name,
                'thana_name' => $thana?->name,
                'area' => $checkoutData['area'] ?? null,
                'address_line' => $checkoutData['address_line'],
                'customer_notes' => $checkoutData['notes'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'product_name' => $item->product->name,
                    'variant_label' => $item->variant?->variant_label,
                    'sku' => $item->variant?->sku ?? $item->product?->sku,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                    'options' => $item->options,
                ]);

                // Reserve inventory in the store's default warehouse (physical stock isn't
                // touched until the order ships — see Order::boot() / commitStockForShippedOrder()).
                if ($item->product->track_inventory && $warehouse) {
                    $stock = WarehouseStock::firstOrCreate(
                        ['warehouse_id' => $warehouse->id, 'product_id' => $item->product_id, 'variant_id' => $item->variant_id],
                        ['quantity' => 0, 'reserved_quantity' => 0]
                    );
                    $stock->increment('reserved_quantity', $item->quantity);
                }
            }

            $order->addStatusHistory('pending', 'Order placed', $checkoutData['name']);

            if ($order->customer) {
                $order->customer->notify(new OrderPlaced($order));
            }

            $cart->items()->delete();
            $cart->delete();

            return $order;
        });
    }

    public function commitStockForShippedOrder(Order $order): void
    {
        $order->load('items.product', 'store');
        $warehouse = $order->store->defaultWarehouse();

        if (! $warehouse) {
            return;
        }

        foreach ($order->items as $item) {
            if (! $item->product || ! $item->product->track_inventory) {
                continue;
            }

            $stock = WarehouseStock::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'product_id' => $item->product_id, 'variant_id' => $item->variant_id],
                ['quantity' => 0, 'reserved_quantity' => 0]
            );

            $stock->decrement('quantity', $item->quantity);
            $stock->decrement('reserved_quantity', min($item->quantity, $stock->reserved_quantity));

            InventoryMovement::create([
                'store_id' => $order->store_id,
                'warehouse_id' => $warehouse->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'order_id' => $order->id,
                'type' => 'sale',
                'quantity_change' => -$item->quantity,
                'quantity_after' => $stock->fresh()->quantity,
                'reason' => "Order {$order->order_number} shipped",
                'created_by' => auth()->user()?->name ?? 'System',
            ]);
        }
    }

    public function restoreStockForOrder(Order $order): void
    {
        $order->load('items.product', 'store');
        $warehouse = $order->store->defaultWarehouse();

        if (! $warehouse) {
            return;
        }

        $wasShipped = $order->shipped_at !== null;

        foreach ($order->items as $item) {
            if (! $item->product || ! $item->product->track_inventory) {
                continue;
            }

            $stock = WarehouseStock::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'product_id' => $item->product_id, 'variant_id' => $item->variant_id],
                ['quantity' => 0, 'reserved_quantity' => 0]
            );

            if ($wasShipped) {
                $stock->increment('quantity', $item->quantity);

                InventoryMovement::create([
                    'store_id' => $order->store_id,
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'order_id' => $order->id,
                    'type' => 'return',
                    'quantity_change' => $item->quantity,
                    'quantity_after' => $stock->fresh()->quantity,
                    'reason' => "Order {$order->order_number} marked {$order->status}",
                    'created_by' => auth()->user()?->name ?? 'System',
                ]);
            } else {
                // Order never shipped — physical stock was never touched, just release the hold.
                $stock->decrement('reserved_quantity', min($item->quantity, $stock->reserved_quantity));
            }
        }
    }

    private function calculateDelivery(int $districtId, float $subtotal, Store $store): float
    {
        $zones = DeliveryZone::where('store_id', $store->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($zones as $zone) {
            if ($zone->type === 'district' && in_array($districtId, $zone->location_ids ?? [])) {
                return $zone->getChargeForOrder($subtotal);
            }
        }

        // Fall back to nationwide zone
        $nationwide = $zones->firstWhere('type', 'nationwide');
        if ($nationwide) {
            return $nationwide->getChargeForOrder($subtotal);
        }

        return (float) $store->getSetting('delivery_outside_dhaka', 120);
    }
}
