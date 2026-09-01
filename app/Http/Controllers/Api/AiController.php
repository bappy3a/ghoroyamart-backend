<?php

namespace App\Http\Controllers\Api;

use App\Ai\Agents\ProductImageSearch;
use App\Http\Controllers\Controller;
use App\Models\AiMessage;
use App\Models\AiMessageDetail;
use App\Models\Category;
use App\Services\AiMessage\AiMessageService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Transcription;
use Throwable;

class AiController extends Controller
{
    use ApiResponse;

    /**
     * Identify a product from a cropped photo (Gemini via Laravel AI SDK).
     */
    public function imageSearch(Request $request)
    {
        if ($request->isMethod('get')) {
            return $this->success([
                'endpoint' => url('/api/ai/image-search'),
                'method' => 'POST',
                'content_type' => 'application/json',
                'body' => [
                    'image' => 'data:image/jpeg;base64,...',
                ],
                'note' => 'Open this URL in a browser uses GET. Send a POST with JSON to analyse a photo.',
            ], null, 'Use POST with an image data URL');
        }

        $validated = $request->validate([
            'image' => ['required', 'string', 'min:32'],
        ]);

        $image = $validated['image'];
        if (! str_starts_with($image, 'data:image/')) {
            return $this->error('Expected an image data URL.', null, ['code' => 'bad_request'], 422);
        }

        $apiKey = trim((string) (config('ai.providers.gemini.key') ?: env('GEMINI_API_KEY') ?: ''));
        if ($apiKey === '') {
            return $this->error(
                'Image search is not configured. Set GEMINI_API_KEY in the Laravel .env.',
                null,
                ['code' => 'not_configured'],
                503
            );
        }

        try {
            [$mime, $base64] = $this->parseDataUrl($image);
            $categories = $this->categoryOptions();

            $response = (new ProductImageSearch($categories))->prompt(
                'What product is this? Give the best store search term.',
                attachments: [
                    Image::fromBase64($base64, $mime),
                ],
                provider: Lab::Gemini,
            );

            $query = trim((string) ($response['query'] ?? ''));
            $rawCategory = trim((string) ($response['category'] ?? ''));
            $label = trim((string) ($response['label'] ?? $query));

            $matched = collect($categories)->first(function (array $c) use ($rawCategory) {
                $needle = strtolower($rawCategory);

                return $needle !== ''
                    && (strtolower($c['slug']) === $needle || strtolower($c['name']) === $needle);
            });

            if ($query === '' && ! $matched) {
                return $this->error(
                    "Couldn't recognise a product in that image.",
                    null,
                    ['code' => 'unrecognised'],
                    422
                );
            }

            return $this->success([
                'query' => mb_substr($query, 0, 60),
                'category' => $matched['slug'] ?? '',
                'label' => mb_substr($label !== '' ? $label : $query, 0, 80),
            ], null, 'Image analysed successfully');
        } catch (Throwable $e) {
            $this->safeLog('AI image search failed', $e);

            return $this->error(
                $this->friendlyAiError($e),
                null,
                ['code' => $this->aiErrorCode($e)],
                $this->aiErrorStatus($e)
            );
        }
    }

    /**
     * Transcribe a short voice clip for storefront search (Gemini STT).
     */
    public function transcribe(Request $request)
    {
        if ($request->isMethod('get')) {
            return $this->success([
                'endpoint' => url('/api/ai/transcribe'),
                'method' => 'POST',
                'content_type' => 'application/json',
                'body' => [
                    'audio' => 'data:audio/webm;base64,...',
                    'mime' => 'audio/webm',
                    'lang' => 'en',
                ],
                'note' => 'Open this URL in a browser uses GET. Send a POST with recorded audio to transcribe.',
            ], null, 'Use POST with audio data');
        }

        $validated = $request->validate([
            'audio' => ['required', 'string', 'min:32'],
            'mime' => ['nullable', 'string', 'max:64'],
            'lang' => ['nullable', 'string', 'max:8'],
        ]);

        $apiKey = trim((string) (config('ai.providers.gemini.key') ?: env('GEMINI_API_KEY') ?: ''));
        if ($apiKey === '') {
            return $this->error(
                'Voice search is not configured. Set GEMINI_API_KEY in the Laravel .env.',
                null,
                ['code' => 'not_configured'],
                503
            );
        }

        try {
            [$mime, $base64] = $this->parseAudioPayload(
                $validated['audio'],
                $validated['mime'] ?? null,
            );

            $lang = strtolower(trim((string) ($validated['lang'] ?? 'en')));
            if ($lang === '' || strlen($lang) > 8) {
                $lang = 'en';
            }

            $response = Transcription::fromBase64($base64, $mime)
                ->language($lang)
                ->timeout(45)
                ->generate(Lab::Gemini);

            $text = trim((string) $response->text);
            if ($text === '') {
                return $this->error(
                    "We couldn't catch that. Try speaking again a bit clearer.",
                    null,
                    ['code' => 'unrecognised'],
                    422
                );
            }

            return $this->success([
                'text' => mb_substr($text, 0, 200),
            ], null, 'Voice transcribed successfully');
        } catch (Throwable $e) {
            $this->safeLog('AI voice transcription failed', $e);

            return $this->error(
                $this->friendlyAiError($e, 'voice'),
                null,
                ['code' => $this->aiErrorCode($e)],
                $this->aiErrorStatus($e)
            );
        }
    }

    /**
     * Auto-reply support chat, backed by AiMessageService (Bangla, product/order aware).
     */
    public function chat(Request $request)
    {
        if ($request->isMethod('get')) {
            $channel = (string) $request->query('channel', AiMessage::CHANNEL_WEBSITE);
            $senderId = (string) $request->query('external_sender_id', '');

            $message = $senderId !== ''
                ? AiMessage::with('details')
                    ->where('channel', $channel)
                    ->where('external_sender_id', $senderId)
                    ->first()
                : null;

            if (! $message) {
                return $this->success([
                    'channel' => $channel,
                    'external_sender_id' => $senderId !== '' ? $senderId : null,
                    'messages' => [
                        [
                            'role' => AiMessage::ROLE_ASSISTANT,
                            'message' => "Hi there 👋 I'm the Agonito assistant. Ask me about products, delivery, returns or your order — I reply instantly.",
                        ],
                    ],
                ], null, 'Conversation not found this is system generated conversation');
            }

            return $this->success([
                'channel' => $message->channel,
                'external_sender_id' => $message->external_sender_id,
                'messages' => $message->details
                    ->map(fn (AiMessageDetail $detail) => [
                        'role' => $detail->message_role,
                        'message' => $detail->message,
                    ])
                    ->values()
                    ->all(),
            ], null, 'Conversation loaded');
        }
        if ($request->isMethod('post')) {
            $request->validate([
                'channel' => ['required', 'string', 'in:facebook,instagram,whatsapp,telegram,website'],
                'external_sender_id' => ['required', 'string', 'max:255'],
                'messages' => ['required', 'array'],
                'messages.role' => ['required', 'string', 'in:user,model,assistant,system'],
                'messages.message' => ['required', 'string', 'max:4000'],
            ]);
            $message = AiMessage::with('details')->firstOrCreate([
                'channel' => $request->channel,
                'external_sender_id' => $request->external_sender_id,
            ]);
            $message->details()->create([
                'message_role' => $request->messages['role'],
                'message' => $request->messages['message'],
            ]);
        }


        $conversationContext = $message->details
            ->map(fn(AiMessageDetail $detail) => [
                'role' => $detail->message_role,
                'message' => $detail->message,
            ])
            ->all();

        $prompt = $request->messages['message'];

        $reply = (new AiMessageService)->generateReply($prompt, $conversationContext);

        $message->details()->create([
            'message_role' => AiMessage::ROLE_MODEL,
            'message' => $reply,
        ]);
        $message->update([
            'last_message' => $reply,
            'last_message_at' => now(),
            'last_message_role' => AiMessage::ROLE_MODEL,
        ]);

        return $this->success(['reply' => $reply], null, 'Reply generated successfully');
    }

    /**
     * @return array{0: string, 1: string} [mime, base64]
     */
    private function parseDataUrl(string $dataUrl): array
    {
        if (! preg_match('#^data:(image/[a-zA-Z0-9.+-]+);base64,(.+)$#s', $dataUrl, $m)) {
            abort(422, 'Invalid image data URL');
        }

        return [$m[1], $m[2]];
    }

    /**
     * @return array{0: string, 1: string} [mime, base64]
     */
    private function parseAudioPayload(string $audio, ?string $mimeHint = null): array
    {
        if (str_starts_with($audio, 'data:')) {
            if (! preg_match('#^data:(audio/[a-zA-Z0-9.+-]+(?:;codecs=[^;,]+)?);base64,(.+)$#s', $audio, $m)) {
                abort(422, 'Invalid audio data URL');
            }

            $mime = strtolower(explode(';', $m[1])[0]);

            return [$mime, $m[2]];
        }

        $mime = $mimeHint ? strtolower(explode(';', $mimeHint)[0]) : 'audio/webm';
        if (! str_starts_with($mime, 'audio/')) {
            $mime = 'audio/webm';
        }

        return [$mime, $audio];
    }

    /**
     * @return list<array{name: string, slug: string}>
     */
    private function categoryOptions(): array
    {
        try {
            return Category::query()
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get(['name', 'slug'])
                ->map(fn(Category $c) => [
                    'name' => (string) $c->name,
                    'slug' => (string) $c->slug,
                ])
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Convert Vercel AI SDK UI messages into Laravel AI history + latest prompt.
     *
     * @param  list<array<string, mixed>>  $messages
     * @return array{0: list<Message>, 1: string}
     */
    private function extractConversation(array $messages): array
    {
        $parsed = [];

        foreach ($messages as $message) {
            if (! is_array($message)) {
                continue;
            }

            $role = (string) ($message['role'] ?? '');
            if (! in_array($role, ['user', 'model', 'system'], true)) {
                continue;
            }

            $text = $this->messageText($message);
            if ($text === '') {
                continue;
            }

            // Skip the canned greeting so Gemini focuses on the live turn.
            if ($role === 'model' && ($message['id'] ?? null) === 'greeting') {
                continue;
            }

            $parsed[] = ['role' => $role, 'text' => $text];
        }

        if ($parsed === []) {
            return [[], ''];
        }

        $last = array_pop($parsed);
        while ($last && $last['role'] !== 'user' && $parsed !== []) {
            $last = array_pop($parsed);
        }

        $prompt = $last && $last['role'] === 'user' ? $last['text'] : '';

        $history = [];
        foreach ($parsed as $row) {
            if ($row['role'] === 'system') {
                continue;
            }
            $history[] = new Message($row['role'], $row['text']);
        }

        return [$history, $prompt];
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function messageText(array $message): string
    {
        if (is_string($message['content'] ?? null)) {
            return trim($message['content']);
        }

        $parts = $message['parts'] ?? null;
        if (! is_array($parts)) {
            return '';
        }

        $chunks = [];
        foreach ($parts as $part) {
            if (! is_array($part)) {
                continue;
            }
            if (($part['type'] ?? null) === 'text' && is_string($part['text'] ?? null)) {
                $chunks[] = $part['text'];
            }
        }

        return trim(implode(' ', $chunks));
    }

    private function safeLog(string $message, Throwable $e): void
    {
        try {
            Log::warning($message, ['error' => $e->getMessage()]);
        } catch (Throwable) {
            // Logging must never turn an AI failure into a 500.
        }
    }

    private function friendlyAiError(Throwable $e, string $feature = 'image'): string
    {
        $raw = $e->getMessage();

        if (str_contains($raw, '403') || str_contains($raw, 'denied access')) {
            return 'Gemini rejected this API key/project (403). Create a Google AI Studio key (AIza…) and set GEMINI_API_KEY.';
        }

        if (str_contains($raw, '401') || str_contains($raw, 'API key not valid') || str_contains($raw, 'INVALID_ARGUMENT')) {
            return 'Gemini API key is invalid. Check GEMINI_API_KEY in the Laravel .env.';
        }

        if (str_contains($raw, '429') || str_contains($raw, 'RESOURCE_EXHAUSTED')) {
            return 'Gemini rate limit hit. Wait a moment and try again.';
        }

        return $feature === 'voice'
            ? "Voice search didn't catch that — try again."
            : 'Could not analyse that image. Try another photo.';
    }

    private function aiErrorCode(Throwable $e): string
    {
        $raw = $e->getMessage();

        if (str_contains($raw, '403') || str_contains($raw, '401') || str_contains($raw, 'denied access')) {
            return 'not_configured';
        }

        return 'failed';
    }

    private function aiErrorStatus(Throwable $e): int
    {
        $raw = $e->getMessage();

        if (str_contains($raw, '403') || str_contains($raw, '401') || str_contains($raw, 'denied access')) {
            return 503;
        }

        if (str_contains($raw, '429')) {
            return 429;
        }

        return 400;
    }
}
