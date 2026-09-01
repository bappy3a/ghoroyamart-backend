<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_page_settings', function (Blueprint $table) {
            $table->id();
            $table->string('page_title')->default('About Us');
            $table->string('breadcrumb_title')->default('About us');
            $table->string('breadcrumb_subtitle')->nullable();
            $table->string('cover_image')->nullable();

            $table->string('section_one_subtitle')->nullable();
            $table->string('section_one_title')->nullable();
            $table->longText('section_one_content')->nullable();
            $table->string('section_one_image')->nullable();

            $table->string('section_two_subtitle')->nullable();
            $table->string('section_two_title')->nullable();
            $table->longText('section_two_content')->nullable();
            $table->string('section_two_image')->nullable();

            $table->string('features_subtitle')->nullable();
            $table->string('features_title')->nullable();
            $table->longText('features_description')->nullable();
            $table->string('feature_one_title')->nullable();
            $table->longText('feature_one_description')->nullable();
            $table->string('feature_two_title')->nullable();
            $table->longText('feature_two_description')->nullable();
            $table->string('feature_three_title')->nullable();
            $table->longText('feature_three_description')->nullable();

            $table->string('reviews_subtitle')->nullable();
            $table->string('reviews_title')->nullable();
            $table->longText('reviews_description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_page_settings');
    }
};
