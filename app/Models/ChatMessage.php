<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['conversation_id', 'sender', 'body', 'agent_label'];
    protected $casts = ['created_at' => 'datetime'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function toClient(): array
    {
        return ['id' => $this->id, 'from' => $this->sender, 'text' => $this->body, 'label' => $this->agent_label, 'time' => $this->created_at?->format('H.i')];
    }
}
