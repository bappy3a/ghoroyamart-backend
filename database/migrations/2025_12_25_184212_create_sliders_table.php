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
        Schema::create('sliders', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Text content fields
            $table->string('subtitle', 191)->nullable()->comment('Small heading (h4)');
            $table->string('title', 191)->nullable()->comment('Main heading (h1)');
            $table->text('description')->nullable()->comment('Description text (p)');
            $table->string('text', 191)->nullable()->comment('Legacy field - kept for backward compatibility');

            // Button fields
            $table->string('button_text', 191)->nullable();
            $table->string('button_link', 191)->nullable();

            // Media fields
            $table->string('image')->nullable();
            $table->string('alt_text', 191)->nullable()->comment('Image alt text for SEO');

            // Status fields
            $table->enum('status', ['draft', 'published', 'archived'])->default('published');
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();

            // Ordering
            $table->integer('sort_order')->default(0);

            // User tracking
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('status');
            $table->index('sort_order');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sliders');
    }
};
