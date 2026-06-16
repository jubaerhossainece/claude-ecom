@extends('storefront.layouts.app')
@section('title', 'Search: ' . $q)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
    <h1 class="text-xl font-bold text-gray-800 mb-2">
        Search results for "<span class="text-primary">{{ $q }}</span>"
    </h1>
    @if($q && $products->isNotEmpty())
        <p class="text-sm text-gray-500 mb-6">{{ $products->total() }} products found</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($products as $product)
                @include('storefront.partials.product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="mt-8">{{ $products->links() }}</div>
    @else
        <p class="text-gray-500 py-12 text-center">
            @if(strlen($q) < 2) Enter at least 2 characters to search.
            @else No results found for "{{ $q }}". Try a different keyword.
            @endif
        </p>
    @endif
</div>
@endsection
