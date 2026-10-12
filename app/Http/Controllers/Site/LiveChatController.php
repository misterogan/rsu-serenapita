<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
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
        $fields = ['name' => $data['name'], 'phone' => $data['phone'], 'phone_norm' => $norm, 'topic' => $data['topic'],
            'status' => 'menunggu', 'started_at' => now(), 'agent_seen_at' => null];
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
        $text = $request->validate(['message' => 'required|string|max:1000'])['message'];
        abort_if($c->status === 'selesai', 422, 'Chat sudah selesai. Mulai chat baru.');

        return response()->json(['message' => $c->addMessage('visitor', $text)->toClient()]);
    }

    public function end(Request $request)
    {
        $c = $this->conv($request);
        $c->addMessage('system', 'Pengunjung mengakhiri chat');
        $c->forceFill(['status' => 'selesai', 'last_at' => now(), 'last_text' => 'Chat diakhiri pengunjung', 'last_from' => 'system'])->save();

        return response()->json(['ok' => true]);
    }
}
