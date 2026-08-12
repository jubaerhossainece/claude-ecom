<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number')->unique();
            // pending, confirmed, processing, shipped, delivered, cancelled, returned
            $table->string('status')->default('pending');
            // cod, sslcommerz, bkash
            $table->string('payment_method')->default('cod');
            // unpaid, paid, refunded, failed
            $table->string('payment_status')->default('unpaid');
            $table->string('payment_reference')->nullable();

            // Totals
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('delivery_charge', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('refunded_amount', 12, 2)->default(0);

            // Customer snapshot (denormalised for history)
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->string('customer_email')->nullable();

            // Delivery address snapshot
            $table->string('division_name')->nullable();
            $table->string('district_name')->nullable();
            $table->string('thana_name')->nullable();
            $table->string('area')->nullable();
            $table->text('address_line');

            $table->foreignId('division_id')->nullable()->constrained();
            $table->foreignId('district_id')->nullable()->constrained();
            $table->foreignId('thana_id')->nullable()->constrained();

            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();

            // COD confirmation
            $table->timestamp('cod_confirmed_at')->nullable();
            $table->string('cod_confirmed_by')->nullable(); // phone agent name

            // Courier
            $table->string('courier_name')->nullable();
            $table->string('courier_tracking_id')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'customer_id']);
            $table->index('order_number');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            // Snapshot fields
            $table->string('product_name');
            $table->string('variant_label')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 12, 2);
            $table->json('options')->nullable(); // variant attributes snapshot
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->text('note')->nullable();
            $table->string('created_by')->nullable(); // admin username
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
