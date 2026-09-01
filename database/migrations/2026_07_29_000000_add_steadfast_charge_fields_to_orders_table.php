<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('steadfast_cod_charger', 10, 2)->default(0)->after('steadfast_order_placed_at');
            $table->decimal('steadfast_delivery_charges', 10, 2)->default(0)->after('steadfast_cod_charger');
        });

        DB::table('orders')
            ->where('payment_method', 'cash_on_delivery')
            ->update([
                'steadfast_cod_charger' => DB::raw('ROUND(total * 0.01, 2)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'steadfast_cod_charger',
                'steadfast_delivery_charges',
            ]);
        });
    }
};
