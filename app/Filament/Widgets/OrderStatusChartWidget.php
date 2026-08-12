<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrderStatusChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Order Status Distribution';

    private const COLORS = [
        'pending' => '#f59e0b',
        'confirmed' => '#3b82f6',
        'processing' => '#6366f1',
        'shipped' => '#8b5cf6',
        'delivered' => '#22c55e',
        'cancelled' => '#ef4444',
        'returned' => '#f43f5e',
    ];

    protected function getData(): array
    {
        $counts = Order::selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status');

        $labels = [];
        $data = [];
        $colors = [];

        foreach (Order::STATUSES as $key => $label) {
            if (($counts[$key] ?? 0) <= 0) {
                continue;
            }
            $labels[] = $label;
            $data[] = $counts[$key];
            $colors[] = self::COLORS[$key] ?? '#9ca3af';
        }

        return [
            'datasets' => [
                ['data' => $data, 'backgroundColor' => $colors],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
