@extends('storefront.layouts.app')
@section('title', 'Order Confirmed!')

@section('content')
@php $symbol = $store?->getSetting('currency_symbol', '৳'); @endphp

<div class="max-w-2xl mx-auto px-4 sm:px-6 py-16 text-center">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
        <div class="text-6xl mb-4">🎉</div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Order Placed Successfully!</h1>
        <p class="text-gray-500 mb-6">Thank you for your order, {{ $order->customer_name }}!</p>

        <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left">
            <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-500">Order Number</span>
                <span class="font-bold text-primary">{{ $order->order_number }}</span>
            </div>
            <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-500">Total Amount</span>
                <span class="font-bold">{{ $symbol }}{{ number_format($order->total, 0) }}</span>
            </div>
            <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-500">Payment Method</span>
                <span>{{ \App\Models\Order::PAYMENT_METHODS[$order->payment_method] ?? $order->payment_method }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Delivery to</span>
                <span class="text-right">{{ implode(', ', array_filter([$order->area, $order->thana_name, $order->district_name])) }}</span>
            </div>
        </div>

        @if($order->payment_method === 'cod' && $codMessage)
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6 text-sm text-yellow-800 text-left">
                📞 {{ $codMessage }}
            </div>
        @endif

        <div class="flex gap-3 justify-center flex-wrap">
            @auth('customer')
                <a href="{{ route('account.orders.show', $order) }}" class="btn-primary">
                    Track Your Order
                </a>
            @endauth
            <a href="{{ route('home') }}" class="border border-gray-300 text-gray-700 px-5 py-2 rounded-lg font-medium hover:bg-gray-50">
                Continue Shopping
            </a>
        </div>
    </div>
</div>
@endsection
