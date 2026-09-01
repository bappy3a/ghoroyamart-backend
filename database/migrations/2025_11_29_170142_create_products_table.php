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
        Schema::create('products', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Basic information
            $table->string('name', 191)->index();
            $table->string('slug', 191)->nullable()->unique();
            $table->string('sku', 191)->nullable()->unique();

            // Description fields
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();

            // Status fields
            $table->enum('status', ['draft', 'published', 'archived', 'scheduled'])->default('published')->comment('draft: 0, published: 1, archived: 2, scheduled: 3');
            $table->enum('visibility', ['public', 'hidden'])->default('public');
            $table->timestamp('published_at')->nullable();

            // Media fields
            $table->string('thumbnail_image')->nullable();
            $table->text('images')->nullable();
            $table->text('video_media')->nullable();

            // Inventory/Stock fields
            $table->unsignedBigInteger('quantity')->default(0);
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'pre_order'])->default('in_stock');
            $table->unsignedInteger('minimum_order_quantity')->default(0);
            $table->unsignedInteger('maximum_order_quantity')->default(0);
            $table->integer('low_stock_alert')->default(0);


            // Pricing fields
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('regular_price', 10, 2)->default(0);
            $table->decimal('price', 10, 2)->default(0)->comment('Current selling price (after discount)');

            // Discount fields
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->date('discount_start_date')->nullable();
            $table->date('discount_end_date')->nullable();
            $table->boolean('is_discounted')->default(false);

            // Feature flags
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(false);
            $table->boolean('is_best_seller')->default(false);
            // Foreign keys - Relationships
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('unit')->default('pcs');

            // Tax & Shipping
            $table->decimal('tax_rate', 5, 2)->nullable();
            // Foreign keys - User tracking
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users')->nullOnDelete();

            // Statistics fields
            $table->bigInteger('num_of_sale')->default(0);
            $table->bigInteger('num_of_views')->default(0);
            $table->bigInteger('num_of_reviews')->default(0);
            $table->decimal('reviews_avg', 3, 2)->default(0)->comment('Average rating from 0.00 to 5.00');

            // SEO Meta fields
            $table->string('meta_title', 191)->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('meta_image')->nullable();


            // Timestamps
            $table->timestamps();

            // Additional indexes for frequently queried columns
            $table->index('status');
            $table->index('visibility');
            $table->index('is_featured');
            $table->index('is_best_seller');
            $table->index('stock_status');
            $table->index('is_discounted');
            // category_id / brand_id already indexed via foreignId()
            $table->index(['status', 'visibility', 'is_featured']);
            $table->index(['status', 'visibility', 'is_best_seller']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
