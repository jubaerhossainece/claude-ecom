<?php

namespace App\Filament\Exports;

use App\Models\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ProductExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name'),
            ExportColumn::make('slug'),
            ExportColumn::make('sku'),
            ExportColumn::make('barcode'),
            ExportColumn::make('category.name')->label('Category'),
            ExportColumn::make('brand.name')->label('Brand'),
            ExportColumn::make('base_price'),
            ExportColumn::make('sale_price'),
            ExportColumn::make('cost_price'),
            ExportColumn::make('status'),
            ExportColumn::make('track_inventory'),
            ExportColumn::make('stock_quantity')
                ->state(fn (Product $record) => $record->stock_quantity),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your product export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
