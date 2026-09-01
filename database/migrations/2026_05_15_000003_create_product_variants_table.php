<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku', 191)->unique();
            $table->string('combination_hash', 191);
            $table->unsignedBigInteger('quantity')->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'combination_hash'], 'pv_product_combination_unique');
            $table->index(['product_id', 'is_active'], 'pv_product_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
