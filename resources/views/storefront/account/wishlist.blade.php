@extends('storefront.layouts.app')
@section('title', 'My Wishlist')

@section('content')
@php $symbol = $store?->getSetting('currency_symbol', '৳'); @endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">❤️ My Wishlist</h1>
        @include('storefront.account.partials.nav')
    </div>

    @if($wishlists->isEmpty())
        <div class="text-center py-16">
            <p class="text-4xl mb-3">💝</p>
            <p class="text-gray-500 mb-4">Your wishlist is empty</p>
            <a href="{{ route('home') }}" class="btn-primary">Discover Products</a>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($wishlists as $wish)
                @continue(! $wish->product)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <a href="{{ route('products.show', $wish->product->slug) }}">
                        <img src="{{ $wish->product->thumbnail_url }}" alt="{{ $wish->product->name }}" class="w-full aspect-square object-cover">
                    </a>
                    <div class="p-3">
                        <a href="{{ route('products.show', $wish->product->slug) }}" class="text-sm font-medium text-gray-800 line-clamp-2 hover:text-primary">
                            {{ $wish->product->name }}
                        </a>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="font-bold text-primary">{{ $symbol }}{{ number_format($wish->current_price, 0) }}</span>
                            @if($wish->has_price_drop)
                                <span class="text-xs text-gray-400 line-through">{{ $symbol }}{{ number_format($wish->price_at_added, 0) }}</span>
                            @endif
                        </div>
                        @if($wish->has_price_drop)
                            <span class="inline-block mt-1 text-[11px] font-medium text-green-700 bg-green-50 px-2 py-0.5 rounded-full">Price dropped!</span>
                        @endif
                        <div class="flex items-center gap-3 mt-3">
                            <form action="{{ route('account.wishlist.move-to-cart', $wish->product) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full text-xs font-medium bg-primary text-white rounded-lg py-1.5 hover:opacity-90">Move to Cart</button>
                            </form>
                            <form action="{{ route('account.wishlist.toggle', $wish->product) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-xs text-gray-400 hover:text-red-500">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
