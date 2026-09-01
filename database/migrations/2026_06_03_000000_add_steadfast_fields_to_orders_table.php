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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('steadfast_consignment_id')->nullable()->after('shipping_method')->index();
            $table->string('steadfast_tracking_code')->nullable()->after('steadfast_consignment_id')->index();
            $table->string('steadfast_status')->nullable()->after('steadfast_tracking_code');
            $table->json('steadfast_response')->nullable()->after('steadfast_status');
            $table->timestamp('steadfast_order_placed_at')->nullable()->after('steadfast_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['steadfast_consignment_id']);
            $table->dropIndex(['steadfast_tracking_code']);
            $table->dropColumn([
                'steadfast_consignment_id',
                'steadfast_tracking_code',
                'steadfast_status',
                'steadfast_response',
                'steadfast_order_placed_at',
            ]);
        });
    }
};
