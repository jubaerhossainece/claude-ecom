<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['store_id', 'slug']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
        });

        $this->backfillFromBrandAttribute();
    }

    /**
     * FakeDataSeeder previously faked "brand" as a plain-text Attribute (slug "brand"),
     * stored per-product in product_attribute_values. This migration promotes those
     * existing values into real Brand rows and sets products.brand_id, then removes
     * the old attribute so there's no dual representation. Uses raw DB::table() only —
     * never the Product/Attribute Eloquent models — since a later part of this same
     * change set may alter what those models' accessors/fillable mean.
     */
    private function backfillFromBrandAttribute(): void
    {
        $now = now();

        $brandAttribute = DB::table('attributes')->where('slug', 'brand')->first();
        if (! $brandAttribute) {
            return;
        }

        $values = DB::table('product_attribute_values')
            ->where('attribute_id', $brandAttribute->id)
            ->get();

        $brandIdByName = [];

        foreach ($values as $value) {
            $name = trim((string) $value->value);
            if ($name === '') {
                continue;
            }

            $product = DB::table('products')->where('id', $value->product_id)->first();
            if (! $product) {
                continue;
            }

            $cacheKey = $product->store_id.'|'.$name;

            if (! isset($brandIdByName[$cacheKey])) {
                $slug = Str::slug($name);
                $existing = DB::table('brands')
                    ->where('store_id', $product->store_id)
                    ->where('slug', $slug)
                    ->value('id');

                $brandIdByName[$cacheKey] = $existing ?: DB::table('brands')->insertGetId([
                    'store_id' => $product->store_id,
                    'name' => $name,
                    'slug' => $slug,
                    'is_active' => true,
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('products')->where('id', $product->id)->update([
                'brand_id' => $brandIdByName[$cacheKey],
            ]);
        }

        DB::table('product_attribute_values')->where('attribute_id', $brandAttribute->id)->delete();
        DB::table('category_attributes')->where('attribute_id', $brandAttribute->id)->delete();
        DB::table('attributes')->where('id', $brandAttribute->id)->delete();
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
        });

        Schema::dropIfExists('brands');
    }
};
