@extends('storefront.layouts.app')
@section('title', 'My Coupons')

@section('content')
@php $symbol = $store?->getSetting('currency_symbol', '৳'); @endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">My Coupons</h1>
        @include('storefront.account.partials.nav')
    </div>

    <h2 class="text-sm font-semibold uppercase text-gray-500 mb-3">Available</h2>
    @forelse($available as $coupon)
        <div class="bg-white rounded-xl border border-dashed border-primary p-4 mb-3 flex items-center justify-between">
            <div>
                <span class="font-bold text-primary tracking-wide">{{ $coupon->code }}</span>
                <p class="text-sm text-gray-500">
                    {{ $coupon->type === 'percentage' ? $coupon->value . '% off' : $symbol . number_format($coupon->value, 0) . ' off' }}
                    @if($coupon->min_order_amount)
                        &middot; min order {{ $symbol }}{{ number_format($coupon->min_order_amount, 0) }}
                    @endif
                </p>
                @if($coupon->description)
                    <p class="text-xs text-gray-400 mt-1">{{ $coupon->description }}</p>
                @endif
            </div>
            @if($coupon->expires_at)
                <span class="text-xs text-gray-400">Expires {{ $coupon->expires_at->format('d M Y') }}</span>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-400 mb-6">No coupons available right now.</p>
    @endforelse

    <h2 class="text-sm font-semibold uppercase text-gray-500 mt-8 mb-3">Used</h2>
    @forelse($used as $coupon)
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 mb-3 flex items-center justify-between opacity-70">
            <span class="font-bold text-gray-600 tracking-wide">{{ $coupon->code }}</span>
            <span class="text-xs text-gray-400">Used</span>
        </div>
    @empty
        <p class="text-sm text-gray-400 mb-6">You haven't used any coupons yet.</p>
    @endforelse

    <h2 class="text-sm font-semibold uppercase text-gray-500 mt-8 mb-3">Expired</h2>
    @forelse($expired as $coupon)
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 mb-3 flex items-center justify-between opacity-50">
            <span class="font-bold text-gray-500 tracking-wide line-through">{{ $coupon->code }}</span>
            <span class="text-xs text-gray-400">Expired {{ $coupon->expires_at->format('d M Y') }}</span>
        </div>
    @empty
        <p class="text-sm text-gray-400">No expired coupons.</p>
    @endforelse
</div>
@endsection
