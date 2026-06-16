@php
    $store = \App\Models\Store::current();
    $symbol = $store->getSetting('currency_symbol', '৳');
    $cart = app(\App\Services\CartService::class)->getOrCreateCart();
    $subtotal = $cart->subtotal;
    $total = max(0, $subtotal - $discountAmount + $deliveryCharge);
@endphp

<form wire:submit="placeOrder" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    {{-- Left: Delivery Info --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl border p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Delivery Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input wire:model="name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" placeholder="Your name">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                    <input wire:model="phone" type="tel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" placeholder="01XXXXXXXXX">
                    @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email (optional)</label>
                    <input wire:model="email" type="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" placeholder="Optional">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Division *</label>
                    <select wire:model.live="division_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary">
                        <option value="">Select Division</option>
                        @foreach($this->divisions as $div)
                            <option value="{{ $div->id }}">{{ $div->name }}</option>
                        @endforeach
                    </select>
                    @error('division_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">District *</label>
                    <select wire:model.live="district_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" {{ !$division_id ? 'disabled' : '' }}>
                        <option value="">Select District</option>
                        @foreach($this->districts as $dist)
                            <option value="{{ $dist->id }}">{{ $dist->name }}</option>
                        @endforeach
                    </select>
                    @error('district_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Thana/Upazila *</label>
                    <select wire:model="thana_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" {{ !$district_id ? 'disabled' : '' }}>
                        <option value="">Select Thana</option>
                        @foreach($this->thanas as $thana)
                            <option value="{{ $thana->id }}">{{ $thana->name }}</option>
                        @endforeach
                    </select>
                    @error('thana_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Area / Locality</label>
                    <input wire:model="area" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none" placeholder="e.g. Mirpur-10, Uttara Sector-7">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Address *</label>
                    <input wire:model="address_line" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none" placeholder="House, Road, Block...">
                    @error('address_line') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Order Notes (optional)</label>
                <textarea wire:model="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none" placeholder="Any special instructions..."></textarea>
            </div>
        </div>

        {{-- Payment method --}}
        <div class="bg-white rounded-xl border p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Payment Method</h2>
            <div class="space-y-3">
                @if($store->getSetting('payment_cod_enabled', true))
                <label class="flex items-center gap-3 border rounded-lg p-3 cursor-pointer {{ $payment_method === 'cod' ? 'border-primary bg-primary/5' : 'border-gray-200' }}">
                    <input wire:model.live="payment_method" type="radio" value="cod" class="text-primary">
                    <span class="text-2xl">💵</span>
                    <div>
                        <p class="font-medium text-sm">Cash on Delivery (COD)</p>
                        <p class="text-xs text-gray-500">Pay when your order arrives</p>
                    </div>
                </label>
                @endif
                @if($store->getSetting('payment_bkash_enabled'))
                <label class="flex items-center gap-3 border rounded-lg p-3 cursor-pointer {{ $payment_method === 'bkash' ? 'border-primary bg-primary/5' : 'border-gray-200' }}">
                    <input wire:model.live="payment_method" type="radio" value="bkash" class="text-primary">
                    <span class="text-2xl">📱</span>
                    <div>
                        <p class="font-medium text-sm">bKash</p>
                        <p class="text-xs text-gray-500">Mobile banking payment</p>
                    </div>
                </label>
                @endif
                @if($store->getSetting('payment_sslcommerz_enabled'))
                <label class="flex items-center gap-3 border rounded-lg p-3 cursor-pointer {{ $payment_method === 'sslcommerz' ? 'border-primary bg-primary/5' : 'border-gray-200' }}">
                    <input wire:model.live="payment_method" type="radio" value="sslcommerz" class="text-primary">
                    <span class="text-2xl">💳</span>
                    <div>
                        <p class="font-medium text-sm">Card / Net Banking (SSLCommerz)</p>
                        <p class="text-xs text-gray-500">Visa, Mastercard, Dutch-Bangla, etc.</p>
                    </div>
                </label>
                @endif
            </div>

            @if($payment_method === 'cod' && $store->getSetting('cod_confirmation_required'))
                <div class="mt-3 bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-800">
                    📞 {{ $store->getSetting('cod_confirmation_message', 'Our agent will call to confirm your order.') }}
                </div>
            @endif
        </div>
    </div>

    {{-- Right: Order Summary --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border p-6 sticky top-24">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Order Summary</h2>

            {{-- Cart items --}}
            <div class="space-y-3 mb-4">
                @foreach($cart->items as $item)
                    <div class="flex gap-3 text-sm">
                        <img src="{{ $item->product?->thumbnail_url }}" alt="{{ $item->product?->name }}" class="w-12 h-12 object-cover rounded-lg flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800 truncate">{{ $item->product?->name }}</p>
                            @if($item->variant) <p class="text-xs text-gray-500">{{ $item->variant->variant_label }}</p> @endif
                            <p class="text-gray-500">x{{ $item->quantity }}</p>
                        </div>
                        <p class="font-medium text-gray-800 flex-shrink-0">{{ $symbol }}{{ number_format($item->subtotal, 0) }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Coupon --}}
            <div class="border-t pt-4 mb-4">
                <div class="flex gap-2">
                    <input wire:model="coupon_code" type="text" placeholder="Coupon code"
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm uppercase focus:outline-none focus:border-primary">
                    <button type="button" wire:click="applyCoupon" class="text-sm font-medium text-primary hover:underline">Apply</button>
                </div>
                @if($couponError) <p class="text-red-500 text-xs mt-1">{{ $couponError }}</p> @endif
                @if($couponSuccess) <p class="text-green-600 text-xs mt-1">{{ $couponSuccess }}</p> @endif
            </div>

            {{-- Totals --}}
            <div class="border-t pt-4 space-y-2 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span>{{ $symbol }}{{ number_format($subtotal, 0) }}</span>
                </div>
                @if($discountAmount > 0)
                    <div class="flex justify-between text-green-600">
                        <span>Discount</span>
                        <span>-{{ $symbol }}{{ number_format($discountAmount, 0) }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-gray-600">
                    <span>Delivery</span>
                    <span>{{ $deliveryCharge > 0 ? $symbol . number_format($deliveryCharge, 0) : '—' }}</span>
                </div>
                <div class="flex justify-between font-bold text-gray-900 text-base border-t pt-2 mt-2">
                    <span>Total</span>
                    <span class="text-primary">{{ $symbol }}{{ number_format($total, 0) }}</span>
                </div>
            </div>

            <button type="submit"
                    class="w-full btn-primary mt-6 py-3 text-center text-base flex items-center justify-center gap-2">
                <span wire:loading.remove>Place Order →</span>
                <span wire:loading>Processing...</span>
            </button>

            @error('cart') <p class="text-red-500 text-xs mt-2 text-center">{{ $message }}</p> @enderror
        </div>
    </div>
</form>
