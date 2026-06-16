<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function index()
    {
        $cart = $this->cartService->getOrCreateCart();
        return view('storefront.cart', compact('cart'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'integer|min:1|max:100',
            'variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $this->cartService->addItem(
            $request->product_id,
            $request->get('quantity', 1),
            $request->variant_id,
        );

        if ($request->expectsJson()) {
            return response()->json(['count' => $this->cartService->getItemCount()]);
        }

        return back()->with('success', 'Item added to cart.');
    }

    public function update(Request $request, int $item)
    {
        $request->validate(['quantity' => 'required|integer|min:0|max:100']);
        $this->cartService->updateItem($item, $request->quantity);

        return back();
    }

    public function remove(int $item)
    {
        $this->cartService->removeItem($item);
        return back()->with('success', 'Item removed.');
    }
}
