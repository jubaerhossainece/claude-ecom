<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One-time data migration: copies the old flat products.stock_quantity /
 * product_variants.stock_quantity values into a per-store default warehouse,
 * ahead of those columns being dropped.
 *
 * Not part of the normal DatabaseSeeder chain — StoreSeeder/FakeDataSeeder
 * already set up warehouses/stock directly for fresh installs. Run manually,
 * once, on a database that still has the old flat columns:
 *
 *   php artisan db:seed --class=WarehouseStockMigrationSeeder
 */
class WarehouseStockMigrationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach (DB::table('stores')->get() as $store) {
            $warehouseId = DB::table('warehouses')->where('store_id', $store->id)->where('is_default', true)->value('id');

            if (! $warehouseId) {
                $warehouseId = DB::table('warehouses')->insertGetId([
                    'store_id' => $store->id,
                    'name' => 'Main Warehouse',
                    'is_default' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $products = DB::table('products')->where('store_id', $store->id)->where('stock_quantity', '>', 0)->get();
            foreach ($products as $product) {
                $exists = DB::table('warehouse_stocks')
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $product->id)
                    ->whereNull('variant_id')
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('warehouse_stocks')->insert([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'quantity' => $product->stock_quantity,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $variants = DB::table('product_variants')
                ->join('products', 'products.id', '=', 'product_variants.product_id')
                ->where('products.store_id', $store->id)
                ->where('product_variants.stock_quantity', '>', 0)
                ->select('product_variants.id', 'product_variants.product_id', 'product_variants.stock_quantity')
                ->get();

            foreach ($variants as $variant) {
                $exists = DB::table('warehouse_stocks')
                    ->where('warehouse_id', $warehouseId)
                    ->where('variant_id', $variant->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('warehouse_stocks')->insert([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'quantity' => $variant->stock_quantity,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
