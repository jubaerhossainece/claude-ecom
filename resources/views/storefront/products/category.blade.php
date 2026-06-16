@extends('storefront.layouts.app')
@section('title', $category->name . ' - ' . ($store->name ?? ''))

@section('content')
@php $symbol = $store?->getSetting('currency_symbol', '৳'); @endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-500 mb-4 flex gap-2 items-center">
        <a href="{{ route('home') }}" class="hover:text-primary">Home</a>
        @if($category->parent)
            <span>/</span>
            <a href="{{ route('products.category', $category->parent) }}" class="hover:text-primary">{{ $category->parent->name }}</a>
        @endif
        <span>/</span>
        <span class="text-gray-800">{{ $category->name }}</span>
    </nav>

    <div class="flex flex-col md:flex-row gap-6">
        {{-- Sidebar Filters --}}
        @if($filterableAttributes->isNotEmpty())
        <aside class="md:w-56 flex-shrink-0">
            <div class="bg-white rounded-xl border p-4">
                <h3 class="font-bold text-gray-800 mb-4">Filter</h3>
                <form method="GET">
                    @foreach($filterableAttributes as $attr)
                        <div class="mb-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">{{ $attr->name }}</p>
                            @if(in_array($attr->type, ['select', 'multiselect']))
                                @foreach($attr->getOptionsArray() as $option)
                                    <label class="flex items-center gap-2 text-sm text-gray-600 mb-1">
                                        <input type="checkbox" name="attr_{{ $attr->slug }}" value="{{ $option['value'] }}"
                                               {{ request('attr_' . $attr->slug) === $option['value'] ? 'checked' : '' }}
                                               class="rounded border-gray-300">
                                        {{ $option['label'] }}
                                    </label>
                                @endforeach
                            @else
                                <input type="text" name="attr_{{ $attr->slug }}" value="{{ request('attr_' . $attr->slug) }}"
                                       class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                            @endif
                        </div>
                    @endforeach
                    <button type="submit" class="w-full btn-primary text-sm mt-2">Apply Filters</button>
                    <a href="{{ route('products.category', $category) }}" class="block text-center text-xs text-gray-500 mt-2 hover:text-primary">Clear filters</a>
                </form>
            </div>
        </aside>
        @endif

        {{-- Products --}}
        <div class="flex-1">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl font-bold text-gray-900">
                    {{ $category->name }}
                    <span class="text-sm font-normal text-gray-500">({{ $products->total() }} products)</span>
                </h1>
                <select onchange="location.href=this.value" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none">
                    @foreach(['newest' => 'Newest First', 'price_asc' => 'Price: Low to High', 'price_desc' => 'Price: High to Low', 'popular' => 'Most Popular'] as $val => $label)
                        <option value="{{ request()->fullUrlWithQuery(['sort' => $val]) }}" {{ $sort === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Subcategories --}}
            @if($subcategories->isNotEmpty())
                <div class="flex gap-3 flex-wrap mb-5">
                    @foreach($subcategories as $sub)
                        <a href="{{ route('products.category', $sub) }}"
                           class="text-sm border rounded-full px-4 py-1.5 hover:border-primary hover:text-primary text-gray-600 transition-colors">
                            {{ $sub->name }} <span class="text-gray-400">({{ $sub->products_count }})</span>
                        </a>
                    @endforeach
                </div>
            @endif

            @if($products->isEmpty())
                <div class="text-center py-16 text-gray-400">
                    <p class="text-4xl mb-3">📦</p>
                    <p class="text-lg">No products found.</p>
                    <a href="{{ route('home') }}" class="btn-primary inline-block mt-4">Go Home</a>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($products as $product)
                        @include('storefront.partials.product-card', ['product' => $product])
                    @endforeach
                </div>
                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
