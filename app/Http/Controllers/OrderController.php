<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        $customer = Auth::guard('customer')->user();
        $orders = $customer->orders()
            ->with('items')
            ->latest()
            ->paginate(10);

        return view('storefront.account.orders', compact('orders'));
    }

    public function show(\App\Models\Order $order)
    {
        $customer = Auth::guard('customer')->user();
        abort_if($order->customer_id !== $customer->id, 403);

        $order->load('items.product', 'statusHistories');

        return view('storefront.account.order-detail', compact('order'));
    }

    public function reorder(\App\Models\Order $order, CartService $cartService)
    {
        $customer = Auth::guard('customer')->user();
        abort_if($order->customer_id !== $customer->id, 403);

        foreach ($order->items as $item) {
            if ($item->product) {
                $cartService->addItem($item->product_id, $item->quantity, $item->variant_id);
            }
        }

        return redirect()->route('cart.index')->with('success', 'Items added to cart.');
    }
}
