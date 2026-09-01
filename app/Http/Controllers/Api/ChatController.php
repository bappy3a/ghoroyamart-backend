<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiMessage\AiMessageService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    use ApiResponse;

    /**
     * Dynamic chat endpoint: send a message, get a Bangla auto-reply back
     * from AiMessageService. Independent of the contact form and of the
     * existing /api/ai/chat streaming endpoint — this returns one plain
     * JSON reply per request.
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:4000'],
            'history' => ['sometimes', 'array'],
            'history.*.role' => ['required_with:history', 'string'],
            'history.*.message' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->error('A message is required.', $validator->errors(), null, 422);
        }

        $validated = $validator->validated();

        $reply = (new AiMessageService)->generateReply(
            $validated['message'],
            $validated['history'] ?? null,
        );

        return $this->success([
            'reply' => $reply,
        ], null, 'Reply generated successfully');
    }
}
