<?php

namespace App\Services\AiMessage;

use App\Services\AiMessage\Agents\CustomerReplyAgent;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;
use Throwable;

/**
 * Standalone entry point for turning a raw customer message into a
 * natural, human-like Bangla reply — backed by real product/order data.
 *
 * This module is intentionally decoupled from the app's existing message
 * server / controllers. Callers only need:
 *
 *     $reply = (new AiMessageService)->generateReply($message, $conversationContext);
 *
 * $conversationContext is an optional list of prior turns, each shaped like
 * ['role' => 'user'|'assistant', 'message' => '...'], oldest first.
 */
class AiMessageService
{
    /**
     * Cap on how many prior turns are sent as context, to keep prompts small.
     */
    private const MAX_HISTORY_TURNS = 20;

    /**
     * Cap on inbound message length, to keep cost/abuse bounded.
     */
    private const MAX_MESSAGE_LENGTH = 4000;

    /**
     * Gemini's free tier briefly rate-limits/overloads under bursty traffic.
     * Retry a couple of times with backoff before giving up to the canned
     * fallback, so a transient blip doesn't look like a broken AI to the
     * customer.
     */
    private const MAX_ATTEMPTS = 3;

    private const RETRY_DELAY_MS = 600;

    public function __construct(
        private readonly Lab|array|string|null $provider = null,
        private readonly ?string $model = null,
    ) {}

    /**
     * Generate a natural, Bangla, human-like reply for a customer message.
     *
     * @param  iterable<int, array{role?: string, message?: string, content?: string, text?: string}>|null  $conversationContext
     */
    public function generateReply(string $message, ?iterable $conversationContext = null): string
    {
        $message = mb_substr(trim($message), 0, self::MAX_MESSAGE_LENGTH);

        if ($message === '') {
            return 'দুঃখিত, আপনার মেসেজটা বুঝতে পারিনি। আরেকবার লিখে পাঠাবেন প্লিজ?';
        }

        $agent = new CustomerReplyAgent($this->buildHistory($conversationContext));

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $response = $agent->prompt($message, provider: $this->provider, model: $this->model);
                $reply = trim((string) $response->text);

                return $reply !== '' ? $reply : $this->fallbackReply();
            } catch (RateLimitedException|ProviderOverloadedException $e) {
                if ($attempt === self::MAX_ATTEMPTS) {
                    $this->safeLog($e);

                    return $this->fallbackReply();
                }

                usleep(self::RETRY_DELAY_MS * 1000 * $attempt);
            } catch (Throwable $e) {
                $this->safeLog($e);

                return $this->fallbackReply();
            }
        }

        return $this->fallbackReply();
    }

    /**
     * @param  iterable<int, array{role?: string, message?: string, content?: string, text?: string}>|null  $conversationContext
     * @return list<Message>
     */
    private function buildHistory(?iterable $conversationContext): array
    {
        if ($conversationContext === null) {
            return [];
        }

        $messages = [];

        foreach ($conversationContext as $turn) {
            if (! is_array($turn)) {
                continue;
            }

            $role = $this->normalizeRole((string) ($turn['role'] ?? ''));
            $text = trim((string) ($turn['message'] ?? $turn['content'] ?? $turn['text'] ?? ''));

            if ($role === null || $text === '') {
                continue;
            }

            $messages[] = new Message($role, $text);
        }

        return array_slice($messages, -self::MAX_HISTORY_TURNS);
    }

    private function normalizeRole(string $role): ?MessageRole
    {
        return match (strtolower($role)) {
            'user', 'customer', 'human' => MessageRole::User,
            'assistant', 'model', 'agent', 'bot', 'ai' => MessageRole::Assistant,
            default => null,
        };
    }

    private function safeLog(Throwable $e): void
    {
        try {
            Log::error('AiMessageService: failed to generate reply', [
                'error' => $e->getMessage(),
            ]);
        } catch (Throwable) {
            // Logging must never turn an AI failure into an unhandled 500.
        }
    }

    private function fallbackReply(): string
    {
        return 'আমাদের সাথে যোগাযোগ করার জন্য ধন্যবাদ! ❤️ আপনার মেসেজটি আমরা পেয়েছি। আমাদের টিম খুব শীঘ্রই আপনার সাথে যোগাযোগ করবে। পাশে থাকার জন্য ধন্যবাদ। 😊';
    }
}
