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
        Schema::table('products', function (Blueprint $table) {
            $table->mediumText('how_to_use')->nullable()->after('description');
            $table->mediumText('good_to_know')->nullable()->after('how_to_use');
            $table->string('warranty', 191)->nullable()->after('good_to_know');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['how_to_use', 'good_to_know', 'warranty']);
        });
    }
};
