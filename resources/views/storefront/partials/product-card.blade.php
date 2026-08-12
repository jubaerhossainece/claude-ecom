@php
    $symbol = $store?->getSetting('currency_symbol', '৳');
@endphp
<div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow border border-gray-200 overflow-hidden group">
    <a href="{{ route('products.show', $product) }}" class="block">
        <div class="aspect-square overflow-hidden bg-gray-100">
            <img src="{{ $product->thumbnail_url }}"
                 alt="{{ $product->name }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                 loading="lazy">
        </div>
        <div class="p-3">
            @if($product->category)
                <p class="text-xs text-gray-400 mb-1">{{ $product->category->name }}</p>
            @endif
            <h3 class="text-sm font-medium text-gray-800 line-clamp-2 group-hover:text-primary">{{ $product->name }}</h3>

            <div class="mt-2 flex items-center gap-2">
                <span class="font-bold text-primary">{{ $symbol }}{{ number_format($product->effective_price, 0) }}</span>
                @if($product->is_on_sale)
                    <span class="text-xs text-gray-400 line-through">{{ $symbol }}{{ number_format($product->base_price, 0) }}</span>
                    <span class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-medium">
                        -{{ round((1 - $product->sale_price / $product->base_price) * 100) }}%
                    </span>
                @endif
            </div>

            @if(!$product->is_in_stock)
                <p class="text-xs text-red-500 mt-1 font-medium">Out of stock</p>
            @elseif($product->is_low_stock)
                <p class="text-xs text-amber-500 mt-1 font-medium">Only {{ $product->stock_quantity }} left</p>
            @endif
        </div>
    </a>
    <div class="px-3 pb-3">
        @livewire('add-to-cart-button', ['product' => $product], key($product->id))
    </div>
</div>
