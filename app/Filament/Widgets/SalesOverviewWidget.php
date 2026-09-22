<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\PageVisit;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $paidStatuses = fn ($query) => $query->whereNotIn('status', ['cancelled', 'returned']);

        $todaySales = $paidStatuses(Order::whereDate('created_at', today()))->sum('total');
        $totalSales = $paidStatuses(Order::query())->sum('total');
        $monthlyRevenue = $paidStatuses(Order::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month))->sum('total');
        $yearlyRevenue = $paidStatuses(Order::whereYear('created_at', now()->year))->sum('total');

        $paidOrderCount = $paidStatuses(Order::query())->count();
        $averageOrderValue = $paidOrderCount > 0 ? $totalSales / $paidOrderCount : 0;

        $periodStart = now()->subDays(30);
        $recentVisits = PageVisit::where('created_at', '>=', $periodStart)->count();
        $recentOrders = Order::where('created_at', '>=', $periodStart)->count();
        $conversionRate = $recentVisits > 0 ? ($recentOrders / $recentVisits) * 100 : null;

        $pendingOrders = Order::where('status', 'pending')->count();
        $totalOrders = Order::count();
        $lowStock = Product::where('track_inventory', true)
            ->get()
            ->filter(fn (Product $product) => $product->is_low_stock)
            ->count();

        return [
            Stat::make('Today\'s Sales', '৳ '.number_format($todaySales, 0))
                ->description('Revenue today')
                ->color('success'),
            Stat::make('Total Sales', '৳ '.number_format($totalSales, 0))
                ->description('All time'),
            Stat::make('Monthly Revenue', '৳ '.number_format($monthlyRevenue, 0))
                ->description(now()->format('F Y')),
            Stat::make('Yearly Revenue', '৳ '.number_format($yearlyRevenue, 0))
                ->description(now()->format('Y')),
            Stat::make('Average Order Value', '৳ '.number_format($averageOrderValue, 0))
                ->description('Per order, all time'),
            Stat::make('Conversion Rate', $conversionRate === null ? 'N/A' : number_format($conversionRate, 1).'%')
                ->description($conversionRate === null ? 'No visits tracked yet' : 'Last 30 days'),
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
