<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
    public function isBlocked(): bool
    {
        return $this->blocked_until && $this->blocked_until->isFuture();
    }

    protected $fillable = ['token', 'name', 'phone', 'phone_norm', 'topic', 'status', 'sessions', 'started_at', 'last_at', 'last_text', 'last_from', 'agent_seen_at', 'agent_id', 'note', 'strikes', 'filtered_total', 'blocked_until'];
    protected $casts = ['started_at' => 'datetime', 'last_at' => 'datetime', 'agent_seen_at' => 'datetime', 'blocked_until' => 'datetime'];
    protected $hidden = ['token', 'note'];

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function isUnseen(): bool
    {
        return $this->last_from === 'visitor' && (! $this->agent_seen_at || $this->last_at > $this->agent_seen_at);
    }

    public static function normalizePhone(string $phone): string
    {
        $d = preg_replace('/\D/', '', $phone);
        if (str_starts_with($d, '62')) {
            $d = '0'.substr($d, 2);
        } elseif (str_starts_with($d, '8')) {
            $d = '0'.$d;
        }

        return $d;
    }

    public function addMessage(string $sender, string $body, ?string $label = null): ChatMessage
    {
        $msg = $this->messages()->create(['sender' => $sender, 'body' => $body, 'agent_label' => $label]);
        if ($sender !== 'system') {
            $this->forceFill(['last_at' => now(), 'last_text' => mb_substr($body, 0, 140), 'last_from' => $sender])->save();
        }

        return $msg;
    }
}
