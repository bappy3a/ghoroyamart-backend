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
        Schema::create('ai_message_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ai_message_id')->constrained('ai_messages')->cascadeOnDelete();

            // user = customer message, model = AI reply, admin = human agent reply, system = internal note. user = customer message
            $table->string('message_role', 20);
            $table->text('message')->nullable();
            $table->json('file_urls')->nullable();
            $table->json('metadata')->nullable();

            $table->text('error')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_message_details');
    }
};
