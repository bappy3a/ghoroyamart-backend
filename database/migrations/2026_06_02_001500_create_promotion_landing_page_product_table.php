<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('promotion_landing_page_product')) {
            Schema::create('promotion_landing_page_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('promotion_landing_page_id')->constrained('promotion_landing_pages')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['promotion_landing_page_id', 'product_id'], 'plpp_unique_pair');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_landing_page_product');
    }
};
