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
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();

            // Groups messages belonging to the same conversation/thread,
            // regardless of channel.
            $table->string('conversation_id', 64)->nullable();

            // Where the message came from: website, facebook, whatsapp, instagram, etc.
            $table->string('channel', 30)->default('website');

            // Platform-provided identifiers, used for webhook dedupe and replies.
            $table->string('external_message_id')->nullable();
            $table->string('external_sender_id')->nullable();

            // Who the message belongs to. Registered customers use user_id,
            // guests are tracked by session_id, social channels by external_sender_id.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id')->nullable();


            $table->boolean('is_ai_generated')->default(true);


            $table->text('last_message')->nullable();
            $table->timestamp('last_message_at')->useCurrent();
            $table->string('last_message_role')->nullable()->comment("user = customer message, admin = human agent reply,assistant = AI reply,system = internal note");
            // Threading: which message this one is a reply to.
            $table->foreignId('replied_to_id')->nullable()->constrained('ai_messages')->nullOnDelete();

            // Raw webhook payload / anything channel-specific.
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('channel');
            $table->index('session_id');
            $table->index('external_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};
