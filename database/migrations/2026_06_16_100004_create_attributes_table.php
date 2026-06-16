<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Attribute definitions (e.g., "Color", "Size", "Weight", "Warranty")
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            // text, number, select, multiselect, boolean, color, date
            $table->string('type')->default('text');
            $table->json('options')->nullable();   // predefined options for select/multiselect
            $table->string('unit')->nullable();    // kg, cm, ml, etc.
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_variant')->default(false);  // can create variants
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['store_id', 'slug']);
        });

        // Which attributes belong to which category
        Schema::create('category_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['category_id', 'attribute_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_attributes');
        Schema::dropIfExists('attributes');
    }
};
