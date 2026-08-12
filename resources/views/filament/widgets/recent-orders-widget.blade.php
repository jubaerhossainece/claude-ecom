<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Recent Orders</x-slot>
        <div class="divide-y divide-gray-100 dark:divide-white/5">
            @forelse($orders as $order)
                <div class="flex items-center justify-between py-2 text-sm">
                    <div>
                        <a href="{{ route('filament.admin.resources.orders.view', $order) }}" class="font-medium text-primary-600 hover:underline">{{ $order->order_number }}</a>
                        <span class="text-gray-500 ml-2">{{ $order->customer_name }}</span>
                    </div>
                    <div class="text-right">
                        <span class="font-medium">৳{{ number_format($order->total, 0) }}</span>
                        <span class="text-xs text-gray-400 block">{{ $order->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 py-4">No orders yet.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
