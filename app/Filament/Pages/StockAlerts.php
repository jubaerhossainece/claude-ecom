<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\Store;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class StockAlerts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string $view = 'filament.pages.stock-alerts';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock Alerts';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Stock Alerts';

    public function table(Table $table): Table
    {
        $alertIds = Product::query()
            ->where('store_id', Store::current()->id)
            ->where('track_inventory', true)
            ->with('warehouseStocks', 'variants.warehouseStocks')
            ->get()
            ->filter(fn (Product $product) => $product->stock_quantity <= 0 || $product->is_low_stock)
            ->pluck('id');

        return $table
            ->query(Product::query()->whereIn('id', $alertIds))
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('sku')->placeholder('—'),
                Tables\Columns\TextColumn::make('category.name')->label('Category'),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Available')
                    ->color(fn (Product $record) => $record->stock_quantity <= 0 ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('reserved_quantity')->label('Reserved'),
                Tables\Columns\TextColumn::make('low_stock_threshold')->label('Threshold'),
                Tables\Columns\TextColumn::make('alert')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Product $record) => $record->stock_quantity <= 0 ? 'Out of Stock' : 'Low Stock')
                    ->color(fn (Product $record) => $record->stock_quantity <= 0 ? 'danger' : 'warning'),
            ])
            ->actions([
                Tables\Actions\Action::make('manage')
                    ->label('Manage Stock')
                    ->icon('heroicon-o-archive-box')
                    ->url(fn (Product $record) => ProductResource::getUrl('index').'?tableSearch='.urlencode($record->name)),
            ]);
    }
}
