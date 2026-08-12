@extends('storefront.layouts.app')

@section('title', $store->getSetting('meta_title', $store->name))

@section('content')
{{-- Hero Banner --}}
<section class="bg-primary text-white" style="background-image: linear-gradient(135deg, var(--color-primary), color-mix(in srgb, var(--color-primary) 70%, black));">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-16 md:py-20 text-center">
        <h1 class="text-3xl md:text-5xl font-bold mb-4 drop-shadow-sm">{{ $store->name }}</h1>
        <p class="text-lg md:text-xl opacity-90 mb-8">{{ $store->tagline }}</p>
        <form action="{{ route('products.search') }}" method="GET" class="max-w-xl mx-auto">
            <div class="flex gap-2">
                <input type="search" name="q" placeholder="Search for products..." required
                       class="flex-1 px-5 py-3 rounded-full text-gray-900 shadow-lg focus:outline-none focus:ring-2 focus:ring-white/50 text-sm">
                <button type="submit" class="bg-white text-primary font-bold px-6 py-3 rounded-full text-sm shadow-lg hover:bg-gray-100 transition-colors">
                    Search
                </button>
            </div>
        </form>
    </div>
</section>

{{-- Categories --}}
@if($categories->isNotEmpty())
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
    <h2 class="text-xl font-bold text-gray-800 mb-6">Shop by Category</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @foreach($categories as $cat)
        <a href="{{ route('products.category', $cat) }}"
           class="bg-white rounded-xl p-4 text-center shadow-sm hover:shadow-md transition-all border border-gray-200 hover:border-primary hover:-translate-y-0.5 group">
            @if($cat->getFirstMedia('image'))
                <img src="{{ $cat->getFirstMedia('image')->getUrl('thumb') }}" alt="{{ $cat->name }}"
                     class="w-16 h-16 object-cover rounded-full mx-auto mb-3 group-hover:scale-105 transition-transform">
            @else
                <div class="w-16 h-16 bg-gray-100 rounded-full mx-auto mb-3 flex items-center justify-center text-2xl">🛍️</div>
            @endif
            <p class="text-sm font-medium text-gray-700 group-hover:text-primary">{{ $cat->name }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $cat->products_count }} items</p>
        </a>
        @endforeach
    </div>
</section>
@endif

{{-- Featured Products --}}
@if($featured->isNotEmpty())
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-800">⭐ Featured Products</h2>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        @foreach($featured as $product)
            @include('storefront.partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endif

{{-- Latest Products --}}
@if($latest->isNotEmpty())
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-800">🆕 New Arrivals</h2>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        @foreach($latest as $product)
            @include('storefront.partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endif

{{-- Trust badges --}}
<section class="bg-gray-50 border-t border-gray-200 mt-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <div class="text-3xl mb-2">🚚</div>
                <h4 class="font-semibold text-gray-800 text-sm">Fast Delivery</h4>
                <p class="text-xs text-gray-500 mt-1">Dhaka 1-2 days, others 3-5 days</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <div class="text-3xl mb-2">💵</div>
                <h4 class="font-semibold text-gray-800 text-sm">Cash on Delivery</h4>
                <p class="text-xs text-gray-500 mt-1">Pay when you receive</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <div class="text-3xl mb-2">🔄</div>
                <h4 class="font-semibold text-gray-800 text-sm">Easy Returns</h4>
                <p class="text-xs text-gray-500 mt-1">Hassle-free returns</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <div class="text-3xl mb-2">📞</div>
                <h4 class="font-semibold text-gray-800 text-sm">Customer Support</h4>
                <p class="text-xs text-gray-500 mt-1">{{ $store->getSetting('support_phone', 'Available 9am-9pm') }}</p>
            </div>
        </div>
    </div>
</section>
@endsection
