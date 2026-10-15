<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $t) {
            $t->unsignedTinyInteger('strikes')->default(0);        // pelanggaran pada sesi ini
            $t->unsignedInteger('filtered_total')->default(0);     // total pesan yang disaring
            $t->timestamp('blocked_until')->nullable();
        });
        // Catatan pesan yang disaring (isi kata kasar sudah disamarkan)
        Schema::create('chat_filtered', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversation_id')->nullable()->constrained('chat_conversations')->cascadeOnDelete();
            $t->string('phone_norm', 20)->nullable()->index();
            $t->string('reason', 20);   // kasar | asal | cepat | ulang
            $t->string('excerpt', 200)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_filtered');
        Schema::table('chat_conversations', function (Blueprint $t) {
            $t->dropColumn(['strikes', 'filtered_total', 'blocked_until']);
        });
    }
};
