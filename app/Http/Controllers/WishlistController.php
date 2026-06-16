<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
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
            Wishlist::create(['customer_id' => $customer->id, 'product_id' => $product->id]);
            $message = 'Added to wishlist.';
        }

        if (request()->expectsJson()) {
            return response()->json(['message' => $message, 'in_wishlist' => ! $existing]);
        }

        return back()->with('success', $message);
    }
}
