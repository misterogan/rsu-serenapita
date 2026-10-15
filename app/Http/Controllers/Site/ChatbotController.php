<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Chatbot;
use App\Support\SiteSettings;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function __invoke(Request $request, Chatbot $bot)
    {
        abort_unless(SiteSettings::get('chatbot')['enabled'], 404);
        $data = $request->validate(['message' => 'required|string|max:500', 'history' => 'nullable|array|max:20']);

        if ((new \App\Services\ChatModerator)->profanity($data['message'])) {
            return response()->json(['answer' => 'Mohon gunakan bahasa yang sopan ya. Saya siap membantu informasi jadwal dokter, layanan, BPJS, dan cara membuat janji temu.']);
        }

        return response()->json(['answer' => $bot->answer($data['message'], $data['history'] ?? [])]);
    }
}
