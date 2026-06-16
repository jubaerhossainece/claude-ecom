@extends('storefront.layouts.app')
@section('title', 'My Wishlist')

@section('content')
@php $symbol = $store?->getSetting('currency_symbol', '৳'); @endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">❤️ My Wishlist</h1>

    @if($wishlists->isEmpty())
        <div class="text-center py-16">
            <p class="text-4xl mb-3">💝</p>
            <p class="text-gray-500 mb-4">Your wishlist is empty</p>
            <a href="{{ route('home') }}" class="btn-primary">Discover Products</a>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($wishlists as $wish)
                @include('storefront.partials.product-card', ['product' => $wish->product])
            @endforeach
        </div>
    @endif
</div>
@endsection
