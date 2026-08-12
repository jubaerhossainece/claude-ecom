@extends('storefront.layouts.app')
@section('title', 'Order ' . $order->order_number)

@section('content')
@php $symbol = $store?->getSetting('currency_symbol', '৳'); @endphp

<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center gap-3 mb-6 justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('account.orders') }}" class="text-gray-400 hover:text-primary">← Back</a>
            <h1 class="text-xl font-bold text-gray-800">Order {{ $order->order_number }}</h1>
        </div>
        <a href="{{ route('account.orders.invoice', $order) }}" target="_blank" class="text-sm text-primary font-medium hover:underline">View Invoice</a>
    </div>

    {{-- Order tracking stepper --}}
    @php
        $steps = [
            'pending' => 'Order Placed',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
        ];
        $stepKeys = array_keys($steps);
        $isTerminalBad = in_array($order->status, ['cancelled', 'returned']);
        $currentIndex = array_search($order->status, $stepKeys);
        if ($currentIndex === false) { $currentIndex = -1; }
    @endphp
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
        <h3 class="font-semibold text-gray-700 mb-4">Order Tracking</h3>

        @if($isTerminalBad)
            <div class="flex items-center gap-2 text-red-600 font-medium text-sm">
                <span>✕</span> Order {{ $order->status_label }}
            </div>
        @else
            <div class="flex items-start">
                @foreach($steps as $key => $label)
                    @php $done = $loop->index <= $currentIndex; @endphp
                    <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                        <div class="flex flex-col items-center">
                            <div @class([
                                'w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0',
                                'bg-primary text-white' => $done,
                                'bg-gray-100 text-gray-400 border border-gray-300' => ! $done,
                            ])>{{ $done ? '✓' : '' }}</div>
                            <span class="text-[11px] text-gray-500 mt-1 text-center w-16">{{ $label }}</span>
                        </div>
                        @unless($loop->last)
                            <div @class([
                                'flex-1 h-0.5 mx-1 mt-3.5',
                                'bg-primary' => $loop->index < $currentIndex,
                                'bg-gray-200' => $loop->index >= $currentIndex,
                            ])></div>
                        @endunless
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h3 class="font-semibold text-gray-700 mb-3">Order Info</h3>
            <div class="space-y-2 text-sm text-gray-600">
                <div class="flex justify-between"><span>Status</span>
                    <span class="font-medium text-primary">{{ $order->status_label }}</span>
                </div>
                <div class="flex justify-between"><span>Payment</span>
                    <span>{{ \App\Models\Order::PAYMENT_METHODS[$order->payment_method] ?? '' }}</span>
                </div>
                <div class="flex justify-between"><span>Date</span>
                    <span>{{ $order->created_at->format('d M Y, h:i A') }}</span>
                </div>
                @if($order->courier_tracking_id)
                    <div class="flex justify-between"><span>Tracking</span>
                        <span class="font-medium">{{ $order->courier_name }} — {{ $order->courier_tracking_id }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h3 class="font-semibold text-gray-700 mb-3">Delivery Address</h3>
            <div class="text-sm text-gray-600">
                <p class="font-medium text-gray-800">{{ $order->customer_name }}</p>
                <p>{{ $order->customer_phone }}</p>
                <p class="mt-1">{{ implode(', ', array_filter([$order->address_line, $order->area, $order->thana_name, $order->district_name, $order->division_name])) }}</p>
            </div>
        </div>
    </div>

    {{-- Items --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
        <h3 class="font-semibold text-gray-700 mb-3">Items Ordered</h3>
        <div class="space-y-3">
            @foreach($order->items as $item)
                <div class="py-2 border-b border-gray-100 last:border-b-0">
                    <div class="flex gap-3">
                        @if($item->product)
                            <img src="{{ $item->product->thumbnail_url }}" alt="{{ $item->product_name }}"
                                 class="w-14 h-14 object-cover rounded-lg flex-shrink-0">
                        @endif
                        <div class="flex-1">
                            <p class="font-medium text-gray-800 text-sm">{{ $item->product_name }}</p>
                            @if($item->variant_label) <p class="text-xs text-gray-500">{{ $item->variant_label }}</p> @endif
                            <p class="text-sm text-gray-500">{{ $symbol }}{{ number_format($item->unit_price, 0) }} × {{ $item->quantity }}</p>
                        </div>
                        <p class="font-bold text-gray-800">{{ $symbol }}{{ number_format($item->subtotal, 0) }}</p>
                    </div>

                    @if($order->status === 'delivered')
                        @if($item->returnRequests->isNotEmpty())
                            @php $rr = $item->returnRequests->first(); @endphp
                            <p class="text-xs text-gray-500 mt-2">
                                {{ \App\Models\ReturnRequest::TYPES[$rr->type] }} request:
                                <span class="font-medium">{{ \App\Models\ReturnRequest::STATUSES[$rr->status] }}</span>
                            </p>
                        @else
                            <div x-data="{ open: false }" class="mt-2">
                                <button type="button" @click="open = !open" class="text-xs text-primary font-medium hover:underline">Request Return / Exchange</button>
                                <form x-show="open" action="{{ route('account.return-requests.store', $item) }}" method="POST" enctype="multipart/form-data" class="space-y-2 mt-2 bg-gray-50 rounded-lg p-3" x-cloak>
                                    @csrf
                                    <select name="type" class="w-full rounded-lg border-gray-300 text-xs">
                                        <option value="return">Return for refund</option>
                                        <option value="exchange">Exchange</option>
                                    </select>
                                    <select name="reason" class="w-full rounded-lg border-gray-300 text-xs" required>
                                        <option value="">Select a reason</option>
                                        @foreach(\App\Models\ReturnRequest::REASONS as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <textarea name="description" rows="2" placeholder="Additional details (optional)" class="w-full rounded-lg border-gray-300 text-xs"></textarea>
                                    <input type="file" name="photos[]" multiple accept="image/*" class="text-xs">
                                    <button type="submit" class="text-xs font-medium bg-primary text-white rounded-lg px-3 py-1.5">Submit Request</button>
                                </form>
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
        <div class="mt-4 space-y-1 text-sm text-gray-600">
            <div class="flex justify-between"><span>Subtotal</span><span>{{ $symbol }}{{ number_format($order->subtotal, 0) }}</span></div>
            @if($order->discount_amount > 0)
                <div class="flex justify-between text-green-600"><span>Discount</span><span>-{{ $symbol }}{{ number_format($order->discount_amount, 0) }}</span></div>
            @endif
            <div class="flex justify-between"><span>Delivery</span><span>{{ $symbol }}{{ number_format($order->delivery_charge, 0) }}</span></div>
            @if($order->tax_amount > 0)
                <div class="flex justify-between"><span>Tax</span><span>{{ $symbol }}{{ number_format($order->tax_amount, 0) }}</span></div>
            @endif
            <div class="flex justify-between font-bold text-gray-900 border-t border-gray-100 pt-2 mt-2 text-base"><span>Total</span><span class="text-primary">{{ $symbol }}{{ number_format($order->total, 0) }}</span></div>
            @if($order->refunded_amount > 0)
                <div class="flex justify-between text-red-600"><span>Refunded</span><span>-{{ $symbol }}{{ number_format($order->refunded_amount, 0) }}</span></div>
            @endif
        </div>
    </div>

    {{-- Status history --}}
    @if($order->statusHistories->isNotEmpty())
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h3 class="font-semibold text-gray-700 mb-4">Status History</h3>
            <div class="space-y-3">
                @foreach($order->statusHistories as $history)
                    <div class="flex gap-3 text-sm">
                        <div class="w-2 h-2 rounded-full bg-primary mt-1.5 flex-shrink-0"></div>
                        <div>
                            <p class="font-medium text-gray-800 capitalize">{{ str_replace('_', ' ', $history->status) }}</p>
                            @if($history->note) <p class="text-gray-500">{{ $history->note }}</p> @endif
                            <p class="text-xs text-gray-400">{{ $history->created_at->format('d M Y, h:i A') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
