<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        $customer = Auth::guard('customer')->user();
        $wishlists = $customer->wishlists()->with('product.media', 'variant')->get();

        return view('storefront.account.wishlist', compact('wishlists'));
    }

    public function toggle(Product $product)
    {
        $customer = Auth::guard('customer')->user();

        $existing = Wishlist::where('customer_id', $customer->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $message = 'Removed from wishlist.';
        } else {
            Wishlist::create([
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'price_at_added' => $product->effective_price,
            ]);
            $message = 'Added to wishlist.';
        }

        if (request()->expectsJson()) {
            return response()->json(['message' => $message, 'in_wishlist' => ! $existing]);
        }

        return back()->with('success', $message);
    }

    public function moveToCart(Product $product, CartService $cartService)
    {
        $customer = Auth::guard('customer')->user();

        $wishlistItem = Wishlist::where('customer_id', $customer->id)
            ->where('product_id', $product->id)
            ->first();

        abort_if(! $wishlistItem, 404);

        $cartService->addItem($product->id, 1, $wishlistItem->variant_id);
        $wishlistItem->delete();

        return back()->with('success', 'Moved to cart.');
    }
}
