<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessageDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_message_id',
        'message_role',
        'message',
        'file_urls',
        'metadata',
        'error',
    ];

    protected $casts = [
        'ai_message_id' => 'integer',
        'message_role' => 'string',
        'message' => 'string',
        'file_urls' => 'array',
        'metadata' => 'array',
        'error' => 'string',
    ];

    public function aiMessage(): BelongsTo
    {
        return $this->belongsTo(AiMessage::class);
    }
}
