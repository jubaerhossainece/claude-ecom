<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'pending'))
                ->badge(Order::where('status', 'pending')->count()),
            'confirmed' => Tab::make('Confirmed')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'confirmed')),
            'shipped' => Tab::make('Shipped')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'shipped')),
            'delivered' => Tab::make('Delivered')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'delivered')),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'cancelled')),
        ];
    }
}
