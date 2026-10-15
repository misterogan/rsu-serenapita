<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Catatan pesan live chat yang disaring (tidak diteruskan ke petugas). */
class ChatFiltered extends Model
{
    public const UPDATED_AT = null;
    protected $table = 'chat_filtered';
    protected $fillable = ['conversation_id', 'phone_norm', 'reason', 'excerpt'];

    public const REASONS = ['kasar' => 'Kata kasar', 'asal' => 'Pesan asal-asalan', 'cepat' => 'Terlalu cepat', 'ulang' => 'Pesan berulang'];
}
