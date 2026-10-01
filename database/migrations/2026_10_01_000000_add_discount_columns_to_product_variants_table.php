<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->double('regular_price')->nullable()->after('quantity');
            $table->string('discount_type', 20)->default('percentage')->after('regular_price')->comment('percentage or amount');
            $table->double('discount_value')->default(0)->after('discount_type');
            $table->double('discount_amount')->default(0)->after('discount_value');
            $table->double('discount_percentage')->default(0)->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['regular_price', 'discount_type', 'discount_value', 'discount_amount', 'discount_percentage']);
        });
    }
};
