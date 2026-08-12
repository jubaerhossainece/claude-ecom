@extends('storefront.layouts.app')
@section('title', 'My Orders')

@section('content')
@php $symbol = $store?->getSetting('currency_symbol', '৳'); @endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">My Orders</h1>
        @include('storefront.account.partials.nav')
    </div>

    @forelse($orders as $order)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-4">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <div>
                    <span class="font-bold text-gray-800">{{ $order->order_number }}</span>
                    <span class="text-xs text-gray-400 ml-3">{{ $order->created_at->format('d M Y') }}</span>
                </div>
                <div class="flex gap-2 items-center">
                    <span @class([
                        'text-xs px-2.5 py-1 rounded-full font-medium',
                        'bg-yellow-100 text-yellow-700' => $order->status === 'pending',
                        'bg-blue-100 text-blue-700' => in_array($order->status, ['confirmed', 'processing']),
                        'bg-purple-100 text-purple-700' => $order->status === 'shipped',
                        'bg-green-100 text-green-700' => $order->status === 'delivered',
                        'bg-red-100 text-red-700' => in_array($order->status, ['cancelled', 'returned']),
                    ])>{{ $order->status_label }}</span>
                    <span class="font-bold text-primary">{{ $symbol }}{{ number_format($order->total, 0) }}</span>
                </div>
            </div>

            <div class="flex gap-3 text-sm flex-wrap">
                @foreach($order->items->take(3) as $item)
                    <span class="text-gray-600">{{ $item->product_name }} x{{ $item->quantity }}</span>
                @endforeach
                @if($order->items->count() > 3)
                    <span class="text-gray-400">+{{ $order->items->count() - 3 }} more</span>
                @endif
            </div>

            <div class="flex gap-3 mt-3">
                <a href="{{ route('account.orders.show', $order) }}" class="text-sm text-primary font-medium hover:underline">View Details</a>
                <form action="{{ route('account.orders.reorder', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-primary">Reorder</button>
                </form>
            </div>
        </div>
    @empty
        <div class="text-center py-16">
            <p class="text-4xl mb-3">📦</p>
            <p class="text-gray-500 mb-4">No orders yet</p>
            <a href="{{ route('home') }}" class="btn-primary">Start Shopping</a>
        </div>
    @endforelse

    {{ $orders->links() }}
</div>
@endsection
