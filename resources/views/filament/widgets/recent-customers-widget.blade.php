<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Recent Registrations</x-slot>
        <div class="divide-y divide-gray-100 dark:divide-white/5">
            @forelse($customers as $customer)
                <div class="flex items-center justify-between py-2 text-sm">
                    <div>
                        <span class="font-medium">{{ $customer->name }}</span>
                        <span class="text-gray-500 ml-2">{{ $customer->phone }}</span>
                    </div>
                    <span class="text-xs text-gray-400">{{ $customer->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-400 py-4">No customers yet.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
