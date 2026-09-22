<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Order;
use App\Models\Store;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private OrderService $orderService,
    ) {}

    public function index()
    {
        $cart = $this->cartService->getOrCreateCart();

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $store = Store::current();
        $customer = Auth::guard('customer')->user();
        $divisions = Division::orderBy('name')->get();

        return view('storefront.checkout', compact('cart', 'store', 'customer', 'divisions'));
    }

    public function store(Request $request)
    {
        $store = Store::current();
        $enabledPayments = array_filter([
            'cod' => (bool) $store->getSetting('payment_cod_enabled', true),
            'sslcommerz' => (bool) $store->getSetting('payment_sslcommerz_enabled', false),
            'bkash' => (bool) $store->getSetting('payment_bkash_enabled', false),
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'division_id' => 'required|exists:divisions,id',
            'district_id' => 'required|exists:districts,id',
            'thana_id' => 'required|exists:thanas,id',
            'area' => 'nullable|string|max:255',
            'address_line' => 'required|string|max:500',
            'payment_method' => 'required|in:'.implode(',', array_keys($enabledPayments)),
            'notes' => 'nullable|string|max:500',
        ]);

        $cart = $this->cartService->getOrCreateCart();

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $customer = Auth::guard('customer')->user();

        $order = $this->orderService->createFromCart($cart, array_merge(
            $request->only(['name', 'phone', 'email', 'division_id', 'district_id', 'thana_id', 'area', 'address_line', 'payment_method', 'notes']),
            ['customer_id' => $customer?->id, 'coupon_code' => $request->coupon_code],
        ));

        return redirect()->route('checkout.success', $order)->with('order_placed', true);
    }

    public function success(Order $order)
    {
        $store = Store::current();
        $codMessage = $store->getSetting('cod_confirmation_message');

        return view('storefront.checkout-success', compact('order', 'codMessage'));
    }
}
