<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Widgets\Widget;

class RecentCustomersWidget extends Widget
{
    protected static string $view = 'filament.widgets.recent-customers-widget';

    protected int|string|array $columnSpan = 1;

    public $customers;

    public function mount(): void
    {
        $this->customers = Customer::latest()->limit(5)->get();
    }
}
