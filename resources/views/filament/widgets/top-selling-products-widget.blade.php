<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Top Selling Products</x-slot>
        <div class="divide-y divide-gray-100 dark:divide-white/5">
            @forelse($products as $product)
                <div class="flex items-center justify-between py-2 text-sm">
                    <span class="font-medium">{{ $product->product_name }}</span>
                    <div class="text-right">
                        <span class="font-medium">৳{{ number_format($product->total_revenue, 0) }}</span>
                        <span class="text-xs text-gray-400 block">{{ $product->total_quantity }} sold</span>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 py-4">No sales yet.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
