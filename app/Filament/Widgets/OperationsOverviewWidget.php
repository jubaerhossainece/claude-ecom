<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $completedOrders = Order::where('status', 'delivered')->count();
        $cancelledOrders = Order::whereIn('status', ['cancelled', 'returned'])->count();
        $totalCustomers = Customer::count();
        $newCustomers = Customer::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count();
        $outOfStock = Product::where('track_inventory', true)
            ->get()
            ->filter(fn (Product $product) => ! $product->is_in_stock)
            ->count();

        return [
            Stat::make('Completed Orders', $completedOrders)
                ->description('Delivered')
                ->color('success'),
            Stat::make('Cancelled / Returned', $cancelledOrders)
                ->color('danger'),
            Stat::make('Total Customers', $totalCustomers),
            Stat::make('New Customers', $newCustomers)
                ->description(now()->format('F Y')),
            Stat::make('Out of Stock', $outOfStock)
                ->description('Products')
                ->color($outOfStock > 0 ? 'danger' : 'success'),
        ];
    }
}
