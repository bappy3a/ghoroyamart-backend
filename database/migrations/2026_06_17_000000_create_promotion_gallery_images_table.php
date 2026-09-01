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
        if (Schema::hasTable('promotion_gallery_images')) {
            return;
        }

        Schema::create('promotion_gallery_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_landing_page_id')
                ->constrained('promotion_landing_pages')
                ->onDelete('cascade');
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['promotion_landing_page_id', 'is_active', 'sort_order'], 'promo_gallery_page_active_sort_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_gallery_images');
    }
};
