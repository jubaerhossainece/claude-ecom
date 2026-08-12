<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueByCategoryChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue by Category';

    protected function getData(): array
    {
        $rows = DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', ['cancelled', 'returned'])
            ->selectRaw('categories.name as category, SUM(order_items.subtotal) as revenue')
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (৳)',
                    'data' => $rows->pluck('revenue')->map(fn ($v) => (float) $v)->toArray(),
                    'backgroundColor' => '#16a34a',
                ],
            ],
            'labels' => $rows->pluck('category')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
