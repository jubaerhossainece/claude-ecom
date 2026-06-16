<?php

namespace App\Livewire;

use App\Models\Coupon;
use App\Models\DeliveryZone;
use App\Models\District;
use App\Models\Division;
use App\Models\Store;
use App\Models\Thana;
use App\Services\CartService;
use App\Services\OrderService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class CheckoutForm extends Component
{
    public string $name = '';
    public string $phone = '';
    public string $email = '';
    public ?int $division_id = null;
    public ?int $district_id = null;
    public ?int $thana_id = null;
    public string $area = '';
    public string $address_line = '';
    public string $payment_method = 'cod';
    public string $coupon_code = '';
    public string $notes = '';

    public float $deliveryCharge = 0;
    public float $discountAmount = 0;
    public ?string $couponError = null;
    public ?string $couponSuccess = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'division_id' => 'required|exists:divisions,id',
            'district_id' => 'required|exists:districts,id',
            'thana_id' => 'required|exists:thanas,id',
            'area' => 'nullable|string|max:255',
            'address_line' => 'required|string|max:500',
            'payment_method' => 'required',
        ];
    }

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();
        if ($customer) {
            $this->name = $customer->name;
            $this->phone = $customer->phone;
            $this->email = $customer->email ?? '';

            $address = $customer->defaultAddress();
            if ($address) {
                $this->division_id = $address->division_id;
                $this->district_id = $address->district_id;
                $this->thana_id = $address->thana_id;
                $this->area = $address->area ?? '';
                $this->address_line = $address->address_line;
            }
        }

        $store = Store::current();
        if ($store->getSetting('payment_cod_enabled', true)) {
            $this->payment_method = 'cod';
        }
    }

    public function getDivisionsProperty()
    {
        return Division::orderBy('name')->get();
    }

    public function getDistrictsProperty()
    {
        return $this->division_id
            ? District::where('division_id', $this->division_id)->orderBy('name')->get()
            : collect();
    }

    public function getThanasProperty()
    {
        return $this->district_id
            ? Thana::where('district_id', $this->district_id)->orderBy('name')->get()
            : collect();
    }

    public function updatedDivisionId(): void
    {
        $this->district_id = null;
        $this->thana_id = null;
        $this->updateDeliveryCharge();
    }

    public function updatedDistrictId(): void
    {
        $this->thana_id = null;
        $this->updateDeliveryCharge();
    }

    private function updateDeliveryCharge(): void
    {
        if (! $this->district_id) {
            $this->deliveryCharge = 0;
            return;
        }

        $store = Store::current();
        $cart = app(CartService::class)->getOrCreateCart();
        $subtotal = $cart->subtotal;

        $zones = DeliveryZone::where('store_id', $store->id)->where('is_active', true)->orderBy('sort_order')->get();

        foreach ($zones as $zone) {
            if ($zone->type === 'district' && in_array($this->district_id, $zone->location_ids ?? [])) {
                $this->deliveryCharge = $zone->getChargeForOrder($subtotal);
                return;
            }
        }

        $nationwide = $zones->firstWhere('type', 'nationwide');
        $this->deliveryCharge = $nationwide ? $nationwide->getChargeForOrder($subtotal) : (float) $store->getSetting('delivery_outside_dhaka', 120);
    }

    public function applyCoupon(CartService $cartService): void
    {
        $this->couponError = null;
        $this->couponSuccess = null;

        if (! $this->coupon_code) return;

        $store = Store::current();
        $cart = $cartService->getOrCreateCart();
        $coupon = Coupon::where('code', strtoupper($this->coupon_code))->where('store_id', $store->id)->first();

        if (! $coupon || ! $coupon->isValid($cart->subtotal)) {
            $this->couponError = 'Invalid or expired coupon code.';
            $this->discountAmount = 0;
            return;
        }

        $this->discountAmount = $coupon->calculateDiscount($cart->subtotal);
        $this->couponSuccess = 'Coupon applied! Saving ৳' . number_format($this->discountAmount, 0);
    }

    public function placeOrder(CartService $cartService, OrderService $orderService)
    {
        $this->validate();

        $cart = $cartService->getOrCreateCart();
        if ($cart->items->isEmpty()) {
            $this->addError('cart', 'Your cart is empty.');
            return;
        }

        $customer = Auth::guard('customer')->user();

        $order = $orderService->createFromCart($cart, [
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email ?: null,
            'division_id' => $this->division_id,
            'district_id' => $this->district_id,
            'thana_id' => $this->thana_id,
            'area' => $this->area,
            'address_line' => $this->address_line,
            'payment_method' => $this->payment_method,
            'coupon_code' => $this->coupon_code,
            'notes' => $this->notes,
            'customer_id' => $customer?->id,
        ]);

        return redirect()->route('checkout.success', $order)->with('order_placed', true);
    }

    public function render()
    {
        return view('livewire.checkout-form');
    }
}
