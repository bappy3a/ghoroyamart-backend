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
        Schema::create('delivery_areas', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->unsignedBigInteger('district_id')->index();
            $table->string('district_name');
            $table->unsignedBigInteger('hub_id')->nullable()->index();
            $table->unsignedTinyInteger('ps_type')->nullable();
            $table->boolean('big_parcel')->default(false);
            $table->string('post_code')->nullable();
            $table->text('address')->nullable();
            $table->text('search_tags')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_areas');
    }
};
