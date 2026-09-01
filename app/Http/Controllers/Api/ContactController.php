<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiMessage;
use App\Models\ContactUs;
use App\Services\AiMessage\AiMessageService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    use ApiResponse;

    /**
     * Store a contact form submission from the storefront.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return $this->error('Please provide valid contact details.', $validator->errors(), null, 422);
        }

        $data = $validator->validated();

        $message = ContactUs::create([
            ...$data,
            'user_id' => $request->user('sanctum')?->id,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255) ?: null,
            'referer' => substr((string) $request->headers->get('referer'), 0, 255) ?: null,
            'status' => 'pending',
        ]);

        // Same identity across submissions keeps the AI aware of this
        // customer's earlier messages, keyed by email since the contact
        // form has no session/channel identifier of its own.
        $thread = AiMessage::with('details')->firstOrCreate([
            'channel' => AiMessage::CHANNEL_WEBSITE,
            'external_sender_id' => strtolower($data['email']),
        ], [
            'user_id' => $request->user('sanctum')?->id,
        ]);

        $conversationContext = $thread->details
            ->map(fn ($detail) => [
                'role' => $detail->message_role,
                'message' => $detail->message,
            ])
            ->all();

        $autoReply = (new AiMessageService)->generateReply($data['message'], $conversationContext);

        $thread->details()->create([
            'message_role' => AiMessage::ROLE_USER,
            'message' => $data['message'],
        ]);
        $thread->details()->create([
            'message_role' => AiMessage::ROLE_MODEL,
            'message' => $autoReply,
        ]);
        $thread->update([
            'last_message' => $autoReply,
            'last_message_at' => now(),
            'last_message_role' => AiMessage::ROLE_MODEL,
        ]);

        return $this->success([
            'id' => $message->id,
            'auto_reply' => $autoReply,
        ], null, 'Your message has been sent successfully.', 201);
    }
}
