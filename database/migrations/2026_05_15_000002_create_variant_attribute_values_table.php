<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_attribute_id')->constrained('variant_attributes')->cascadeOnDelete();
            $table->string('value', 191);
            $table->string('slug', 191);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['variant_attribute_id', 'value'], 'vav_attribute_value_unique');
            $table->unique(['variant_attribute_id', 'slug'], 'vav_attribute_slug_unique');
            $table->index(['variant_attribute_id', 'sort_order'], 'vav_attribute_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_attribute_values');
    }
};
