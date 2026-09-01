<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('cancelled_quantity')->default(0)->after('quantity');
            $table->unsignedInteger('restocked_quantity')->default(0)->after('cancelled_quantity');
            $table->string('item_status')->default('active')->after('restocked_quantity');
            $table->string('cancelled_by_type')->nullable()->after('item_status');
            $table->foreignId('cancelled_by_id')->nullable()->after('cancelled_by_type')->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable()->after('cancelled_by_id');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');

            $table->index(['item_status', 'cancelled_quantity']);
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['item_status', 'cancelled_quantity']);
            $table->dropConstrainedForeignId('cancelled_by_id');
            $table->dropColumn([
                'cancelled_quantity',
                'restocked_quantity',
                'item_status',
                'cancelled_by_type',
                'cancellation_reason',
                'cancelled_at',
            ]);
        });
    }
};
