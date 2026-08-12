<div class="flex items-center gap-2">
    <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
        <button wire:click="decrement" class="px-2 py-1 text-gray-600 hover:bg-gray-100">−</button>
        <span class="px-3 py-1 text-sm font-medium border-x border-gray-300">{{ $quantity }}</span>
        <button wire:click="increment" class="px-2 py-1 text-gray-600 hover:bg-gray-100">+</button>
    </div>

    <button wire:click="addToCart"
            @class(['flex-1 btn-primary text-sm py-1.5 text-center transition-all', 'opacity-50 cursor-not-allowed' => !$product->is_in_stock])
            @if(!$product->is_in_stock) disabled @endif>
        <span wire:loading.remove>
            @if($added) ✓ Added! @elseif(!$product->is_in_stock) Out of Stock @else Add to Cart @endif
        </span>
        <span wire:loading class="flex items-center justify-center gap-1">
            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
        </span>
    </button>
</div>
