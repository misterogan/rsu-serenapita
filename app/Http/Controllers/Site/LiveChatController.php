<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatFiltered;
use App\Services\ChatModerator;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** API live chat untuk pengunjung. Pengunjung dikenali lewat token acak yang disimpan di browser. */
class LiveChatController extends Controller
{
    public const TOPICS = ['Informasi umum', 'Jadwal dokter', 'Janji temu', 'BPJS dan administrasi', 'Lainnya'];

    protected function conv(Request $request): ChatConversation
    {
        $token = (string) $request->input('token', $request->header('X-Chat-Token'));
        abort_if(strlen($token) < 32, 404);

        return ChatConversation::where('token', $token)->firstOrFail();
    }

    protected function state(ChatConversation $c, int $after = 0): array
    {
        return [
            'status' => $c->status, 'name' => $c->name, 'phone' => $c->phone, 'topic' => $c->topic,
            'messages' => $c->messages()->where('id', '>', $after)->orderBy('id')->limit(300)->get()->map->toClient(),
        ];
    }

    public function start(Request $request)
    {
        $cfg = SiteSettings::get('livechat');
        abort_unless($cfg['enabled'], 403, 'Live chat sedang tidak aktif.');
        $data = $request->validate([
            'name' => 'required|string|min:3|max:100',
            'phone' => 'required|string|max:20',
            'topic' => 'required|in:'.implode(',', self::TOPICS),
            'message' => 'required|string|min:2|max:1000',
            'consent' => 'accepted',
            'token' => 'nullable|string|max:64',
        ], [], ['name' => 'nama', 'phone' => 'nomor HP', 'topic' => 'topik', 'message' => 'pesan', 'consent' => 'persetujuan']);

        $norm = ChatConversation::normalizePhone($data['phone']);
        if (! preg_match('/^08[1-9]\d{6,11}$/', $norm)) {
            throw ValidationException::withMessages(['phone' => 'Nomor HP tidak valid. Contoh: 081234567890.']);
        }

        $c = ! empty($data['token']) ? ChatConversation::where('token', $data['token'])->first() : null;

        // Nomor atau perangkat yang sedang diblokir sementara
        $blocked = ChatConversation::where('phone_norm', $norm)->where('blocked_until', '>', now())->orderByDesc('blocked_until')->first()
            ?? ($c?->isBlocked() ? $c : null);
        if ($blocked) {
            return $this->blockedResponse($blocked);
        }

        // Saring nama dan pesan pertama. Pesan yang tersaring tidak membuat percakapan baru,
        // sehingga petugas tidak menerima notifikasi apa pun.
        $mod = new ChatModerator($cfg);
        if ($r = $mod->check($data['name'], 'nama')) {
            ChatFiltered::create(['phone_norm' => $norm, 'reason' => $r['reason'], 'excerpt' => 'Nama: '.$r['masked']]);
            throw ValidationException::withMessages(['name' => $r['message']]);
        }
        if ($r = $mod->check($data['message'])) {
            ChatFiltered::create(['phone_norm' => $norm, 'reason' => $r['reason'], 'excerpt' => $r['masked']]);
            throw ValidationException::withMessages(['message' => $r['message']]);
        }

        $fields = ['name' => $data['name'], 'phone' => $data['phone'], 'phone_norm' => $norm, 'topic' => $data['topic'],
            'status' => 'menunggu', 'started_at' => now(), 'agent_seen_at' => null, 'strikes' => 0];
        if ($c) {
            $c->fill($fields + ['sessions' => $c->sessions + 1])->save();
        } else {
            $c = ChatConversation::create($fields + ['token' => Str::random(48), 'sessions' => 1]);
        }

        $c->addMessage('system', 'Chat dimulai · '.$data['topic']);
        $c->addMessage('system', str_replace('{nama}', explode(' ', $data['name'])[0], $cfg['welcome']));
        $c->addMessage('visitor', $data['message']);

        return response()->json(['token' => $c->token] + $this->state($c));
    }

    public function poll(Request $request)
    {
        $c = $this->conv($request);

        return response()->json($this->state($c, (int) $request->query('after', 0)));
    }

    public function send(Request $request)
    {
        $c = $this->conv($request);
        $text = trim($request->validate(['message' => 'required|string|max:1000'])['message']);
        if ($c->isBlocked()) {
            return $this->blockedResponse($c);
        }
        abort_if($c->status === 'selesai', 422, 'Chat sudah selesai. Mulai chat baru.');
        $cfg = SiteSettings::get('livechat');

        // Terlalu banyak pesan dalam waktu singkat
        $recent = $c->messages()->where('sender', 'visitor')->where('created_at', '>=', now()->subSeconds(30))->count();
        if ($recent >= 6) {
            ChatFiltered::create(['conversation_id' => $c->id, 'phone_norm' => $c->phone_norm, 'reason' => 'cepat', 'excerpt' => mb_substr($text, 0, 200)]);
            $c->increment('filtered_total');

            return response()->json(['message' => 'Mohon tunggu sebentar. Pesan Anda terlalu cepat, petugas akan membalas.'], 429);
        }
        // Pesan yang sama persis dikirim ulang
        $last = $c->messages()->where('sender', 'visitor')->where('created_at', '>=', now()->subMinutes(3))->latest('id')->first();
        if ($last && mb_strtolower(trim($last->body)) === mb_strtolower($text)) {
            return response()->json(['message' => 'Pesan yang sama sudah terkirim. Mohon tunggu balasan petugas.'], 422);
        }

        if ($r = (new ChatModerator($cfg))->check($text)) {
            ChatFiltered::create(['conversation_id' => $c->id, 'phone_norm' => $c->phone_norm, 'reason' => $r['reason'], 'excerpt' => $r['masked']]);
            $max = max(1, (int) ($cfg['max_strikes'] ?? 3));
            $c->forceFill(['strikes' => $c->strikes + 1, 'filtered_total' => $c->filtered_total + 1])->save();
            if ($c->strikes >= $max) {
                $hours = max(1, (int) ($cfg['block_hours'] ?? 24));
                $c->addMessage('system', 'Chat dihentikan otomatis karena beberapa pesan tidak sesuai aturan. Untuk keperluan mendesak, silakan hubungi rumah sakit lewat telepon.');
                $c->forceFill(['status' => 'selesai', 'blocked_until' => now()->addHours($hours), 'last_at' => now(), 'last_text' => 'Chat dihentikan otomatis (pesan disaring)', 'last_from' => 'system'])->save();

                return $this->blockedResponse($c);
            }

            return response()->json(['message' => $r['message'].' (Peringatan '.$c->strikes.' dari '.$max.')'], 422);
        }

        return response()->json(['message' => $c->addMessage('visitor', $text)->toClient()]);
    }

    protected function blockedResponse(ChatConversation $c)
    {
        $phone = SiteSettings::phones()->firstWhere('show_igd', true) ?? SiteSettings::phones()->first();

        return response()->json([
            'blocked' => true,
            'message' => 'Live chat untuk Anda dibatasi sementara sampai '.$c->blocked_until->translatedFormat('d M Y H.i').' karena pesan yang tidak sesuai aturan.'
                .($phone ? ' Untuk keperluan mendesak, hubungi '.$phone->number.'.' : ''),
        ], 423);
    }

    public function end(Request $request)
    {
        $c = $this->conv($request);
        $c->addMessage('system', 'Pengunjung mengakhiri chat');
        $c->forceFill(['status' => 'selesai', 'last_at' => now(), 'last_text' => 'Chat diakhiri pengunjung', 'last_from' => 'system'])->save();

        return response()->json(['ok' => true]);
    }
}
