<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $todaySales = Order::whereDate('created_at', today())
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->sum('total');

        $pendingOrders = Order::where('status', 'pending')->count();
        $totalOrders = Order::count();
        $lowStock = Product::where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0)
            ->count();

        return [
            Stat::make('Today\'s Sales', '৳ ' . number_format($todaySales, 0))
                ->description('Revenue today')
                ->color('success'),
            Stat::make('Pending Orders', $pendingOrders)
                ->description('Need attention')
                ->color('warning')
                ->url(route('filament.admin.resources.orders.index', ['activeTab' => 'pending'])),
            Stat::make('Total Orders', $totalOrders)
                ->description('All time'),
            Stat::make('Low Stock Products', $lowStock)
                ->description('Need restocking')
                ->color($lowStock > 0 ? 'danger' : 'success'),
        ];
    }
}
