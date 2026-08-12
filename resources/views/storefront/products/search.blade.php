@extends('storefront.layouts.app')
@section('title', 'Search: ' . $q)

@section('content')
@php $symbol = $store?->getSetting('currency_symbol', '৳'); @endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
    <h1 class="text-xl font-bold text-gray-800 mb-2">
        Search results for "<span class="text-primary">{{ $q }}</span>"
    </h1>

    @if(strlen($q) < 2)
        <div class="text-center py-16 text-gray-400">
            <p class="text-4xl mb-3">🔍</p>
            <p class="text-lg text-gray-500">Enter at least 2 characters to search.</p>
        </div>
    @else
        <div class="flex flex-col md:flex-row gap-6">
            {{-- Sidebar Filters --}}
            <aside class="md:w-56 flex-shrink-0">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <h3 class="font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100">Filter</h3>
                    <form method="GET">
                        <input type="hidden" name="q" value="{{ $q }}">
                        <div class="mb-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">Category</p>
                            <select name="category_id" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ (string) request('category_id') === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">Price Range</p>
                            <div class="flex gap-2">
                                <input type="number" name="price_min" value="{{ request('price_min') }}" placeholder="Min" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                                <input type="number" name="price_max" value="{{ request('price_max') }}" placeholder="Max" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                            </div>
                        </div>
                        <button type="submit" class="w-full btn-primary text-sm mt-2">Apply Filters</button>
                        <a href="{{ route('products.search', ['q' => $q]) }}" class="block text-center text-xs text-gray-500 mt-2 hover:text-primary">Clear filters</a>
                    </form>
                </div>

                @if($popularSearches->isNotEmpty())
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mt-4">
                        <h3 class="font-bold text-gray-800 mb-3 text-sm">Popular Searches</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($popularSearches as $term)
                                <a href="{{ route('products.search', ['q' => $term]) }}" class="text-xs border border-gray-200 rounded-full px-3 py-1 text-gray-600 hover:border-primary hover:text-primary">{{ $term }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>

            {{-- Products --}}
            <div class="flex-1">
                @if($products->isNotEmpty())
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-sm text-gray-500">{{ $products->total() }} products found</p>
                        <select onchange="location.href=this.value" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                            @foreach(['relevance' => 'Best Match', 'newest' => 'Newest First', 'price_asc' => 'Price: Low to High', 'price_desc' => 'Price: High to Low'] as $val => $label)
                                <option value="{{ request()->fullUrlWithQuery(['sort' => $val]) }}" {{ $sort === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach($products as $product)
                            @include('storefront.partials.product-card', ['product' => $product])
                        @endforeach
                    </div>
                    <div class="mt-8">{{ $products->links() }}</div>
                @else
                    <div class="text-center py-16 text-gray-400">
                        <p class="text-4xl mb-3">🔍</p>
                        <p class="text-lg text-gray-500">No results found for "{{ $q }}". Try a different keyword.</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
