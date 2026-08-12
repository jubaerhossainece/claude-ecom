<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\Widget;

class RecentOrdersWidget extends Widget
{
    protected static string $view = 'filament.widgets.recent-orders-widget';

    protected int|string|array $columnSpan = 1;

    public $orders;

    public function mount(): void
    {
        $this->orders = Order::latest()->limit(5)->get();
    }
}
