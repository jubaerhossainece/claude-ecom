<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class PaymentMethodChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Payment Methods';

    private const COLORS = [
        'cod' => '#6b7280',
        'sslcommerz' => '#3b82f6',
        'bkash' => '#e2136e',
    ];

    protected function getData(): array
    {
        $counts = Order::selectRaw('payment_method, count(*) as count')->groupBy('payment_method')->pluck('count', 'payment_method');

        $labels = [];
        $data = [];
        $colors = [];

        foreach (Order::PAYMENT_METHODS as $key => $label) {
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
        return 'pie';
    }
}
