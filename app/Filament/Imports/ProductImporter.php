<?php

namespace App\Filament\Imports;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Store;
use App\Models\WarehouseStock;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('slug')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('sku')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('barcode')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('category')
                ->relationship('category', 'name'),

            ImportColumn::make('brand')
                ->fillRecordUsing(function (Product $record, ?string $state) {
                    if (blank($state)) {
                        return;
                    }

                    $brand = Brand::firstOrCreate(
                        ['store_id' => Store::current()->id, 'slug' => Str::slug($state)],
                        ['name' => $state]
                    );

                    $record->brand_id = $brand->id;
                }),

            ImportColumn::make('base_price')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric', 'min:0']),

            ImportColumn::make('sale_price')
                ->numeric()
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('cost_price')
                ->numeric()
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('status')
                ->rules(['nullable', 'in:'.implode(',', Product::STATUSES)]),

            ImportColumn::make('track_inventory')
                ->boolean(),

            ImportColumn::make('stock_quantity')
                ->numeric()
                ->fillRecordUsing(fn () => null),
        ];
    }

    public function resolveRecord(): ?Model
    {
        $storeId = Store::current()->id;

        if (filled($this->data['sku'] ?? null)) {
            $existing = Product::where('store_id', $storeId)->where('sku', $this->data['sku'])->first();
            if ($existing) {
                return $existing;
            }
        }

        if (filled($this->data['slug'] ?? null)) {
            $existing = Product::where('store_id', $storeId)->where('slug', $this->data['slug'])->first();
            if ($existing) {
                return $existing;
            }
        }

        $product = new Product;
        $product->store_id = $storeId;
        $product->status = 'draft';
        $product->unit_of_sale = 'piece';
        $product->track_inventory = true;

        return $product;
    }

    protected function afterFill(): void
    {
        if (blank($this->record->slug)) {
            $this->record->slug = Str::slug($this->record->name).'-'.Str::lower(Str::random(4));
        }
    }

    protected function afterSave(): void
    {
        if (! array_key_exists('stock_quantity', $this->data) || blank($this->data['stock_quantity'])) {
            return;
        }

        $warehouse = Store::current()->defaultWarehouse();
        if (! $warehouse) {
            return;
        }

        $stock = WarehouseStock::firstOrCreate(
            ['warehouse_id' => $warehouse->id, 'product_id' => $this->record->id, 'variant_id' => null],
            ['quantity' => 0]
        );
        $stock->update(['quantity' => (int) $this->data['stock_quantity']]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
