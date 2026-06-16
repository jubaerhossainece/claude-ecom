@extends('storefront.layouts.app')
@section('title', 'Shopping Cart')

@section('content')
@php $symbol = $store?->getSetting('currency_symbol', '৳'); @endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">🛒 Shopping Cart</h1>

    @if($cart->items->isEmpty())
        <div class="text-center py-16">
            <p class="text-5xl mb-4">🛍️</p>
            <p class="text-lg text-gray-500 mb-6">Your cart is empty</p>
            <a href="{{ route('home') }}" class="btn-primary">Continue Shopping</a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-3">
                @foreach($cart->items as $item)
                    <div class="bg-white rounded-xl border p-4 flex gap-4 items-center">
                        <a href="{{ route('products.show', $item->product) }}">
                            <img src="{{ $item->product?->thumbnail_url }}" alt="{{ $item->product?->name }}"
                                 class="w-20 h-20 object-cover rounded-lg flex-shrink-0">
                        </a>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('products.show', $item->product) }}" class="font-medium text-gray-800 hover:text-primary">
                                {{ $item->product?->name }}
                            </a>
                            @if($item->variant)
                                <p class="text-xs text-gray-500 mt-0.5">{{ $item->variant->variant_label }}</p>
                            @endif
                            <p class="text-sm text-gray-500 mt-1">{{ $symbol }}{{ number_format($item->unit_price, 0) }} each</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <form action="{{ route('cart.update', $item) }}" method="POST" class="flex items-center border rounded-lg overflow-hidden">
                                @csrf @method('PATCH')
                                <button type="submit" name="quantity" value="{{ max(1, $item->quantity - 1) }}"
                                        class="px-2 py-1 text-gray-600 hover:bg-gray-100">−</button>
                                <span class="px-3 py-1 text-sm font-medium border-x">{{ $item->quantity }}</span>
                                <button type="submit" name="quantity" value="{{ $item->quantity + 1 }}"
                                        class="px-2 py-1 text-gray-600 hover:bg-gray-100">+</button>
                            </form>
                            <span class="font-bold text-primary w-20 text-right">{{ $symbol }}{{ number_format($item->subtotal, 0) }}</span>
                            <form action="{{ route('cart.remove', $item) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-600 p-1">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl border p-6 sticky top-24">
                    <h2 class="font-bold text-gray-800 mb-4">Summary</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Items ({{ $cart->total_items }})</span>
                            <span>{{ $symbol }}{{ number_format($cart->subtotal, 0) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Delivery</span>
                            <span class="text-xs text-gray-400">Calculated at checkout</span>
                        </div>
                        <div class="flex justify-between font-bold text-gray-900 text-base border-t pt-2 mt-2">
                            <span>Subtotal</span>
                            <span class="text-primary">{{ $symbol }}{{ number_format($cart->subtotal, 0) }}</span>
                        </div>
                    </div>
                    <a href="{{ route('checkout.index') }}" class="btn-primary block text-center mt-5 py-3">
                        Proceed to Checkout →
                    </a>
                    <a href="{{ route('home') }}" class="block text-center text-sm text-gray-500 hover:text-primary mt-3">
                        ← Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
