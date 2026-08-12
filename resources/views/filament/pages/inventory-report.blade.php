<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-filament::section>
            <x-slot name="heading">On-Hand Value</x-slot>
            <p class="text-2xl font-bold">৳{{ number_format($totalValue, 0) }}</p>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">Total On-Hand Units</x-slot>
            <p class="text-2xl font-bold">{{ number_format($totalOnHand) }}</p>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">Reserved Units</x-slot>
            <p class="text-2xl font-bold">{{ number_format($totalReserved) }}</p>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">Low / Out of Stock</x-slot>
            <p class="text-2xl font-bold text-warning-600">{{ $lowStockCount }} <span class="text-sm font-normal text-gray-400">low</span>
                &nbsp;<span class="text-danger-600">{{ $outOfStockCount }}</span> <span class="text-sm font-normal text-gray-400">out</span></p>
        </x-filament::section>
    </div>

    <x-filament::section class="mt-4">
        <x-slot name="heading">Stock by Warehouse</x-slot>
        <x-slot name="headerEnd">
            <a href="{{ route('admin.inventory-report.export') }}" target="_blank">
                <x-filament::button icon="heroicon-o-arrow-down-tray" color="gray" size="sm">
                    Download CSV
                </x-filament::button>
            </a>
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 dark:text-gray-400">
                        <th class="py-2 pr-4">Warehouse</th>
                        <th class="py-2 pr-4">Products</th>
                        <th class="py-2 pr-4">On Hand</th>
                        <th class="py-2 pr-4">Reserved</th>
                        <th class="py-2 pr-4">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse($warehouseRows as $row)
                        <tr>
                            <td class="py-2 pr-4 font-medium">{{ $row->warehouse_name }}</td>
                            <td class="py-2 pr-4">{{ number_format($row->product_count) }}</td>
                            <td class="py-2 pr-4">{{ number_format($row->on_hand) }}</td>
                            <td class="py-2 pr-4">{{ number_format($row->reserved) }}</td>
                            <td class="py-2 pr-4">৳{{ number_format($row->value, 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-gray-400">No warehouse stock recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
