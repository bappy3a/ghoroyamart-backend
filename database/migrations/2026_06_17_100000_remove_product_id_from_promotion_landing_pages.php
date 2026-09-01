<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kept for existing environments that still have product_id on
     * promotion_landing_pages. Fresh installs create the table without it.
     */
    public function up(): void
    {
        if (! Schema::hasTable('promotion_landing_pages')) {
            return;
        }

        if (! Schema::hasColumn('promotion_landing_pages', 'product_id')) {
            return;
        }

        if (Schema::hasTable('promotion_landing_page_product')) {
            $records = DB::table('promotion_landing_pages')
                ->select('id', 'product_id')
                ->whereNotNull('product_id')
                ->get();

            foreach ($records as $record) {
                DB::table('promotion_landing_page_product')->updateOrInsert([
                    'promotion_landing_page_id' => $record->id,
                    'product_id' => $record->product_id,
                ], [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('promotion_landing_pages', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }

    public function down(): void
    {
        // No-op: product links live on the pivot table.
    }
};
