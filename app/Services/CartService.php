<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartService
{
    private string $sessionKey = 'cart_id';

    public function getOrCreateCart(): Cart
    {
        $store = Store::current();
        $customer = Auth::guard('customer')->user();

        if ($customer) {
            $cart = Cart::firstOrCreate(
                ['store_id' => $store->id, 'customer_id' => $customer->id],
                ['expires_at' => now()->addDays(30)]
            );

            // Merge session cart if exists
            if ($sessionCartId = Session::get($this->sessionKey)) {
                $this->mergeSessionCart($cart, $sessionCartId);
                Session::forget($this->sessionKey);
            }

            return $cart->load('items.product', 'items.variant');
        }

        $cartId = Session::get($this->sessionKey);
        if ($cartId) {
            $cart = Cart::find($cartId);
            if ($cart) {
                return $cart->load('items.product', 'items.variant');
            }
        }

        $cart = Cart::create([
            'store_id' => $store->id,
            'session_id' => Session::getId(),
            'expires_at' => now()->addDays(7),
        ]);
        Session::put($this->sessionKey, $cart->id);

        return $cart;
    }

    public function addItem(int $productId, int $quantity = 1, ?int $variantId = null): CartItem
    {
        $cart = $this->getOrCreateCart();
        $product = Product::findOrFail($productId);
        $variant = $variantId ? ProductVariant::findOrFail($variantId) : null;

        $price = $variant ? $variant->effective_price : $product->effective_price;

        $existing = $cart->items()
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();

        if ($existing) {
            $existing->increment('quantity', $quantity);

            return $existing;
        }

        return $cart->items()->create([
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'unit_price' => $price,
            'options' => $variant?->attribute_values ?? [],
        ]);
    }

    public function updateItem(int $cartItemId, int $quantity): void
    {
        $cart = $this->getOrCreateCart();
        $item = $cart->items()->findOrFail($cartItemId);

        if ($quantity <= 0) {
            $item->delete();
        } else {
            $item->update(['quantity' => $quantity]);
        }
    }

    public function removeItem(int $cartItemId): void
    {
        $cart = $this->getOrCreateCart();
        $cart->items()->where('id', $cartItemId)->delete();
    }

    public function clear(): void
    {
        $cart = $this->getOrCreateCart();
        $cart->items()->delete();
        $cart->update(['coupon_code' => null]);
    }

    public function getItemCount(): int
    {
        $cartId = Session::get($this->sessionKey);
        if (! $cartId && ! Auth::guard('customer')->check()) {
            return 0;
        }

        $cart = $this->getOrCreateCart();

        return $cart->items->sum('quantity');
    }

    private function mergeSessionCart(Cart $userCart, int $sessionCartId): void
    {
        $sessionCart = Cart::with('items')->find($sessionCartId);
        if (! $sessionCart) {
            return;
        }

        foreach ($sessionCart->items as $item) {
            $existing = $userCart->items()
                ->where('product_id', $item->product_id)
                ->where('variant_id', $item->variant_id)
                ->first();

            if ($existing) {
                $existing->increment('quantity', $item->quantity);
            } else {
                $userCart->items()->create($item->only(['product_id', 'variant_id', 'quantity', 'unit_price', 'options']));
            }
        }

        $sessionCart->delete();
    }
}
