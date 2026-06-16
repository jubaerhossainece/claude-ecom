<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\DeliveryZone;
use App\Models\District;
use App\Models\Division;
use App\Models\Order;
use App\Models\Store;
use App\Models\Thana;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createFromCart(Cart $cart, array $checkoutData): Order
    {
        return DB::transaction(function () use ($cart, $checkoutData) {
            $store = Store::current();
            $cart->load('items.product', 'items.variant');

            $subtotal = $cart->subtotal;
            $discountAmount = 0;
            $couponId = null;

            if (! empty($checkoutData['coupon_code'])) {
                $coupon = Coupon::where('code', $checkoutData['coupon_code'])
                    ->where('store_id', $store->id)
                    ->first();

                if ($coupon && $coupon->isValid($subtotal)) {
                    $discountAmount = $coupon->calculateDiscount($subtotal);
                    $couponId = $coupon->id;
                    $coupon->increment('used_count');
                }
            }

            $deliveryCharge = $this->calculateDelivery($checkoutData['district_id'], $subtotal, $store);
            $total = max(0, $subtotal - $discountAmount + $deliveryCharge);

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

                // Deduct inventory
                if ($item->product->track_inventory) {
                    if ($item->variant) {
                        $item->variant->decrement('stock_quantity', $item->quantity);
                    } else {
                        $item->product->decrement('stock_quantity', $item->quantity);
                    }
                }
            }

            $order->addStatusHistory('pending', 'Order placed', $checkoutData['name']);

            $cart->items()->delete();
            $cart->delete();

            return $order;
        });
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
