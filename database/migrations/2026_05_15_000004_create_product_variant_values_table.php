<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('variant_attribute_id')->constrained('variant_attributes')->restrictOnDelete();
            $table->foreignId('variant_attribute_value_id')->constrained('variant_attribute_values')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['product_variant_id', 'variant_attribute_id'], 'pvv_variant_attribute_unique');
            $table->index(['variant_attribute_id', 'variant_attribute_value_id'], 'pvv_attribute_value_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_values');
    }
};
