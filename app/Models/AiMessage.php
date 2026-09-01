<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiMessage extends Model
{
    use HasFactory, SoftDeletes;

    public const CHANNEL_WEBSITE = 'website';
    public const CHANNEL_FACEBOOK = 'facebook';
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_INSTAGRAM = 'instagram';

    public const ROLE_USER = 'user';
    public const ROLE_ASSISTANT = 'assistant';
    public const ROLE_MODEL = 'model';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SYSTEM = 'system';

    public const TYPE_TEXT = 'text';
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_AUDIO = 'audio';
    public const TYPE_FILE = 'file';
    public const TYPE_LOCATION = 'location';
    public const TYPE_STICKER = 'sticker';

    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ = 'read';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'conversation_id',
        'channel',
        'external_message_id',
        'external_sender_id',
        'user_id',
        'session_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'role',
        'is_ai_generated',
        'type',
        'message',
        'attachment_path',
        'attachment_url',
        'attachment_mime',
        'attachment_size',
        'status',
        'delivered_at',
        'read_at',
        'replied_to_id',
        'last_message',
        'last_message_at',
        'last_message_role',
        'metadata',
    ];

    protected $casts = [
        'is_ai_generated' => 'boolean',
        'attachment_size' => 'integer',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function repliedTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replied_to_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'replied_to_id');
    }

    public function detail(): HasOne
    {
        return $this->hasOne(AiMessageDetail::class);
    }

    public function hasAttachment(): bool
    {
        return filled($this->attachment_path) || filled($this->attachment_url);
    }

    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeConversation($query, string $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function details(): HasMany
    {
        return $this->hasMany(AiMessageDetail::class)->orderBy('created_at', 'asc')->select('id', 'ai_message_id', 'message_role', 'message', 'file_urls', 'created_at');
    }
}
