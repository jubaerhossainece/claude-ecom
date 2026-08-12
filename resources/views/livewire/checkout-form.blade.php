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
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100">Delivery Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input wire:model="name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="Your name">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                    <input wire:model="phone" type="tel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="01XXXXXXXXX">
                    @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email (optional)</label>
                    <input wire:model="email" type="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="Optional">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Division *</label>
                    <select wire:model.live="division_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 disabled:bg-gray-50 disabled:text-gray-400">
                        <option value="">Select Division</option>
                        @foreach($this->divisions as $div)
                            <option value="{{ $div->id }}">{{ $div->name }}</option>
                        @endforeach
                    </select>
                    @error('division_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">District *</label>
                    <select wire:model.live="district_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 disabled:bg-gray-50 disabled:text-gray-400" {{ !$division_id ? 'disabled' : '' }}>
                        <option value="">Select District</option>
                        @foreach($this->districts as $dist)
                            <option value="{{ $dist->id }}">{{ $dist->name }}</option>
                        @endforeach
                    </select>
                    @error('district_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Thana/Upazila *</label>
                    <select wire:model="thana_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 disabled:bg-gray-50 disabled:text-gray-400" {{ !$district_id ? 'disabled' : '' }}>
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
                    <input wire:model="area" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="e.g. Mirpur-10, Uttara Sector-7">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Address *</label>
                    <input wire:model="address_line" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="House, Road, Block...">
                    @error('address_line') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Order Notes (optional)</label>
                <textarea wire:model="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="Any special instructions..."></textarea>
            </div>
        </div>

        {{-- Payment method --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100">Payment Method</h2>
            <div class="space-y-3">
                @if($store->getSetting('payment_cod_enabled', true))
                <label class="flex items-center gap-3 border rounded-lg p-3 cursor-pointer transition-colors {{ $payment_method === 'cod' ? 'border-primary ring-1 ring-primary/20 bg-primary/5' : 'border-gray-200 hover:border-gray-300' }}">
                    <input wire:model.live="payment_method" type="radio" value="cod" class="text-primary focus:ring-primary/30">
                    <span class="text-2xl">💵</span>
                    <div>
                        <p class="font-medium text-sm text-gray-800">Cash on Delivery (COD)</p>
                        <p class="text-xs text-gray-500">Pay when your order arrives</p>
                    </div>
                </label>
                @endif
                @if($store->getSetting('payment_bkash_enabled'))
                <label class="flex items-center gap-3 border rounded-lg p-3 cursor-pointer transition-colors {{ $payment_method === 'bkash' ? 'border-primary ring-1 ring-primary/20 bg-primary/5' : 'border-gray-200 hover:border-gray-300' }}">
                    <input wire:model.live="payment_method" type="radio" value="bkash" class="text-primary focus:ring-primary/30">
                    <span class="text-2xl">📱</span>
                    <div>
                        <p class="font-medium text-sm text-gray-800">bKash</p>
                        <p class="text-xs text-gray-500">Mobile banking payment</p>
                    </div>
                </label>
                @endif
                @if($store->getSetting('payment_sslcommerz_enabled'))
                <label class="flex items-center gap-3 border rounded-lg p-3 cursor-pointer transition-colors {{ $payment_method === 'sslcommerz' ? 'border-primary ring-1 ring-primary/20 bg-primary/5' : 'border-gray-200 hover:border-gray-300' }}">
                    <input wire:model.live="payment_method" type="radio" value="sslcommerz" class="text-primary focus:ring-primary/30">
                    <span class="text-2xl">💳</span>
                    <div>
                        <p class="font-medium text-sm text-gray-800">Card / Net Banking (SSLCommerz)</p>
                        <p class="text-xs text-gray-500">Visa, Mastercard, Dutch-Bangla, etc.</p>
                    </div>
                </label>
                @endif
            </div>

            @if($payment_method === 'cod' && $store->getSetting('cod_confirmation_required'))
                <div class="mt-3 bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
                    📞 {{ $store->getSetting('cod_confirmation_message', 'Our agent will call to confirm your order.') }}
                </div>
            @endif
        </div>
    </div>

    {{-- Right: Order Summary --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden sticky top-24">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100">Order Summary</h2>

                {{-- Cart items --}}
                <div class="divide-y divide-gray-100 mb-4">
                    @foreach($cart->items as $item)
                        <div class="flex gap-3 text-sm py-3 first:pt-0 last:pb-0">
                            <img src="{{ $item->product?->thumbnail_url }}" alt="{{ $item->product?->name }}" class="w-12 h-12 object-cover rounded-lg border border-gray-100 flex-shrink-0">
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
                <div class="border-t border-gray-100 pt-4 mb-4">
                    <div class="flex gap-2">
                        <input wire:model="coupon_code" type="text" placeholder="Coupon code"
                               class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm uppercase focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                        <button type="button" wire:click="applyCoupon" class="text-sm font-semibold text-primary border border-primary rounded-lg px-3 py-1.5 hover:bg-primary hover:text-white transition-colors">Apply</button>
                    </div>
                    @if($couponError) <p class="text-red-500 text-xs mt-1">{{ $couponError }}</p> @endif
                    @if($couponSuccess) <p class="text-green-600 text-xs mt-1">{{ $couponSuccess }}</p> @endif
                </div>

                {{-- Totals --}}
                <div class="border-t border-gray-100 pt-4 space-y-2 text-sm">
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
                </div>
            </div>

            <div class="bg-gray-50 border-t border-gray-100 px-6 py-4">
                <div class="flex justify-between font-bold text-gray-900 text-base">
                    <span>Total</span>
                    <span class="text-primary">{{ $symbol }}{{ number_format($total, 0) }}</span>
                </div>

                <button type="submit"
                        class="w-full btn-primary mt-4 py-3 text-center text-base flex items-center justify-center gap-2">
                    <span wire:loading.remove>Place Order →</span>
                    <span wire:loading>Processing...</span>
                </button>

                @error('cart') <p class="text-red-500 text-xs mt-2 text-center">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
</form>
