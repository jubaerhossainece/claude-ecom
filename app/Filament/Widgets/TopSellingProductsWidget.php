<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class TopSellingProductsWidget extends Widget
{
    protected static string $view = 'filament.widgets.top-selling-products-widget';

    protected int|string|array $columnSpan = 1;

    public $products;

    public function mount(): void
    {
        $this->products = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', ['cancelled', 'returned'])
            ->selectRaw('order_items.product_id, order_items.product_name, SUM(order_items.quantity) as total_quantity, SUM(order_items.subtotal) as total_revenue')
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();
    }
}
