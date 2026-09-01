<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['product_wise', 'order_based'])->default('order_based');
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 10, 2);
            $table->decimal('minimum_order_amount', 10, 2)->nullable()->default(0);
            $table->decimal('maximum_discount_amount', 10, 2)->nullable();
            $table->json('product_ids')->nullable()->comment('Product IDs for product_wise type (stored as JSON array)');
            $table->date('valid_from');
            $table->date('valid_to');
            $table->integer('usage_limit')->nullable()->comment('Total usage limit for coupon');
            $table->integer('usage_limit_per_user')->nullable()->comment('Usage limit per user');
            $table->integer('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('code');
            $table->index('type');
            $table->index('is_active');
            $table->index(['valid_from', 'valid_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
