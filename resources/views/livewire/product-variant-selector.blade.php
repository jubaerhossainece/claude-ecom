@php
    $symbol = app(\App\Models\Store::class)::current()->getSetting('currency_symbol', '৳');
    $effectiveVariant = $selectedVariant ?? null;
    $displayPrice = $effectiveVariant
        ? $effectiveVariant->effective_price
        : $product->effective_price;
@endphp

<div>
    {{-- Variant attributes --}}
    @foreach($this->variantAttributes as $slug => $values)
        <div class="mb-4">
            <p class="text-sm font-semibold text-gray-700 mb-2 capitalize">{{ str_replace('-', ' ', $slug) }}</p>
            <div class="flex gap-2 flex-wrap">
                @foreach($values as $value)
                    <button
                        wire:click="$set('selectedAttributes.{{ $slug }}', '{{ $value }}')"
                        @class([
                            'px-3 py-1.5 text-sm border rounded-lg transition-all',
                            'border-primary text-primary bg-primary/5 font-medium' => ($selectedAttributes[$slug] ?? null) === $value,
                            'border-gray-300 text-gray-600 hover:border-gray-400' => ($selectedAttributes[$slug] ?? null) !== $value,
                        ])>
                        {{ $value }}
                    </button>
                @endforeach
            </div>
        </div>
    @endforeach

    @error('variant')
        <p class="text-red-500 text-sm mb-3">{{ $message }}</p>
    @enderror

    {{-- Price for selected variant --}}
    @if($effectiveVariant)
        <div class="text-lg font-bold text-primary mb-4">
            {{ $symbol }}{{ number_format($effectiveVariant->effective_price, 0) }}
            @if(!$effectiveVariant->is_in_stock)
                <span class="text-sm text-red-500 font-normal ml-2">Out of Stock</span>
            @endif
        </div>
    @endif

    {{-- Quantity + Add to Cart --}}
    <div class="flex items-center gap-3">
        <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
            <button wire:click="$set('quantity', Math.max(1, quantity - 1))" class="px-3 py-2 text-gray-600 hover:bg-gray-100">−</button>
            <span class="px-4 py-2 text-sm font-medium border-x border-gray-300">{{ $quantity }}</span>
            <button wire:click="$set('quantity', quantity + 1)" class="px-3 py-2 text-gray-600 hover:bg-gray-100">+</button>
        </div>

        <button wire:click="addToCart"
                @class(['flex-1 btn-primary py-2.5 text-sm', 'opacity-50 cursor-not-allowed' => ($effectiveVariant && !$effectiveVariant->is_in_stock)])
                @if($effectiveVariant && !$effectiveVariant->is_in_stock) disabled @endif>
            <span wire:loading.remove>
                @if($added) ✓ Added to Cart!
                @elseif(!$selectedVariantId && count($this->variantAttributes) > 0) Select Options
                @else Add to Cart @endif
            </span>
            <span wire:loading>Adding...</span>
        </button>
    </div>
</div>
