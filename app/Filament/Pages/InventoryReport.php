<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Store;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class InventoryReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.pages.inventory-report';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Inventory Report';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Inventory Report';

    public function getViewData(): array
    {
        $store = Store::current();

        $warehouseRows = DB::table('warehouse_stocks')
            ->join('products', 'products.id', '=', 'warehouse_stocks.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'warehouse_stocks.warehouse_id')
            ->where('products.store_id', $store->id)
            ->select(
                'warehouses.id as warehouse_id',
                'warehouses.name as warehouse_name',
                DB::raw('SUM(warehouse_stocks.quantity) as on_hand'),
                DB::raw('SUM(warehouse_stocks.reserved_quantity) as reserved'),
                DB::raw('SUM(warehouse_stocks.quantity * COALESCE(products.cost_price, 0)) as value'),
                DB::raw('COUNT(DISTINCT warehouse_stocks.product_id) as product_count')
            )
            ->groupBy('warehouses.id', 'warehouses.name')
            ->orderBy('warehouses.name')
            ->get();

        $trackedProducts = Product::where('store_id', $store->id)
            ->where('track_inventory', true)
            ->with('warehouseStocks', 'variants.warehouseStocks')
            ->get();

        $lowStockCount = $trackedProducts->filter(fn (Product $p) => $p->is_low_stock)->count();
        $outOfStockCount = $trackedProducts->filter(fn (Product $p) => $p->stock_quantity <= 0)->count();

        return [
            'warehouseRows' => $warehouseRows,
            'totalValue' => $warehouseRows->sum('value'),
            'totalOnHand' => $warehouseRows->sum('on_hand'),
            'totalReserved' => $warehouseRows->sum('reserved'),
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
        ];
    }
}
