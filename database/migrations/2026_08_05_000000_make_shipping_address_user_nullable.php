<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kept for existing environments. Fresh installs already create
     * shipping_addresses.user_id as nullable with nullOnDelete.
     */
    public function up(): void
    {
        if (! Schema::hasTable('shipping_addresses')) {
            return;
        }

        Schema::table('shipping_addresses', function (Blueprint $table) {
            if (Schema::hasColumn('shipping_addresses', 'user_id')) {
                $table->foreignId('user_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Intentionally left empty: reverting would break guest checkout addresses.
    }
};
