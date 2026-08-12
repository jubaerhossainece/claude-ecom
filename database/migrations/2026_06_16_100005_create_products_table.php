<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            // draft, active, inactive, archived
            $table->string('status')->default('draft');
            $table->boolean('is_featured')->default(false);
            // piece, kg, gram, litre, pack, dozen, bundle
            $table->string('unit_of_sale')->default('piece');
            $table->decimal('base_price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->integer('low_stock_threshold')->default(5);
            $table->boolean('track_inventory')->default(true);
            $table->boolean('allow_backorder')->default(false);
            $table->decimal('weight', 8, 3)->nullable(); // in kg
            $table->json('dimensions')->nullable();       // {length, width, height} in cm
            $table->integer('sort_order')->default(0);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['store_id', 'slug']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'category_id']);
        });

        // Flat attribute values per product (non-variant attributes)
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->text('value')->nullable();         // text/number/boolean
            $table->json('value_json')->nullable();    // for multiselect/complex
            $table->timestamps();
            $table->unique(['product_id', 'attribute_id']);
        });

        // Product variants (e.g., Red-XL, Blue-M)
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->json('attribute_values');   // {attribute_slug: value, ...}
            $table->string('variant_label')->nullable(); // "Red / XL"
            $table->decimal('price', 12, 2)->nullable();      // override
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('weight', 8, 3)->nullable(); // in kg, overrides product weight
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('products');
    }
};
