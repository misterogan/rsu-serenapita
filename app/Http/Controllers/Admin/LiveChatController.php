<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\ChatConversation;
use App\Support\SiteSettings;
use Illuminate\Http\Request;

class LiveChatController extends Controller
{
    public function index()
    {
        return view('admin.livechat', ['cfg' => SiteSettings::get('livechat')]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:60', 'hours' => 'nullable|string|max:60',
            'welcome' => 'required|string|max:300', 'canned' => 'nullable|string|max:3000',
        ]);
        $data['canned'] = array_values(array_filter(array_map('trim', preg_split('/\R/', $data['canned'] ?? ''))));
        $data['enabled'] = $request->boolean('enabled');
        SiteSettings::put('livechat', $data);
        ActivityLog::record('Memperbarui pengaturan live chat');

        return back()->with('ok', 'Pengaturan live chat disimpan.');
    }

    /** Daftar percakapan untuk konsol (dipanggil berkala). */
    public function feed()
    {
        $convs = ChatConversation::query()->orderByDesc('last_at')->limit(300)->get();
        $phoneCounts = $convs->countBy('phone_norm');

        return response()->json([
            'conversations' => $convs->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'phone' => $c->phone, 'phone_norm' => $c->phone_norm, 'topic' => $c->topic,
                'status' => $c->status, 'last_text' => $c->last_text, 'last_from' => $c->last_from,
                'last_at' => $c->last_at?->toIso8601String(), 'last_short' => $c->last_at?->isToday() ? $c->last_at->format('H.i') : $c->last_at?->translatedFormat('d M'),
                'unseen' => $c->isUnseen(), 'same_phone' => ($phoneCounts[$c->phone_norm] ?? 1) - 1,
            ]),
            'count' => $this->countValue(),
        ]);
    }

    public function count(Request $request)
    {
        return response()->json(['count' => $request->user()->canModule('livechat') ? $this->countValue() : 0]);
    }

    protected function countValue(): int
    {
        return ChatConversation::where('status', 'menunggu')
            ->orWhere(fn ($q) => $q->where('status', 'aktif')->where('last_from', 'visitor')->where(fn ($w) => $w->whereNull('agent_seen_at')->orWhereColumn('last_at', '>', 'agent_seen_at')))
            ->count();
    }

    /** Detail pengunjung + pesan baru sejak id tertentu. Sekaligus menandai pesan sudah dibaca. */
    public function show(Request $request, ChatConversation $conversation)
    {
        $after = (int) $request->query('after', 0);
        $messages = $conversation->messages()->where('id', '>', $after)->orderBy('id')->limit(500)->get();
        if ($conversation->isUnseen()) {
            $conversation->forceFill(['agent_seen_at' => now()])->save();
        }
        $others = ChatConversation::where('phone_norm', $conversation->phone_norm)->where('id', '!=', $conversation->id)->pluck('name');
        $appts = Appointment::with('doctor')->where('phone', $conversation->phone_norm)->orderByDesc('date')->limit(3)->get();

        return response()->json([
            'conversation' => [
                'id' => $conversation->id, 'name' => $conversation->name, 'phone' => $conversation->phone, 'phone_norm' => $conversation->phone_norm,
                'topic' => $conversation->topic, 'status' => $conversation->status, 'sessions' => $conversation->sessions, 'note' => $conversation->note,
                'started_at' => $conversation->started_at?->translatedFormat('d M Y H.i'), 'created_at' => $conversation->created_at?->translatedFormat('d M Y H.i'),
                'agent' => $conversation->agent?->name,
                'others' => $others, 'appointments' => $appts->map(fn ($a) => $a->code.' · '.($a->doctor?->name ?? '').' · '.$a->date->translatedFormat('d M Y').' · '.$a->status),
            ],
            'messages' => $messages->map->toClient(),
        ]);
    }

    public function send(Request $request, ChatConversation $conversation)
    {
        $text = $request->validate(['message' => 'required|string|max:2000'])['message'];
        abort_if($conversation->status === 'selesai', 422, 'Percakapan sudah selesai.');
        $label = SiteSettings::get('livechat')['label'];
        $msg = $conversation->addMessage('agent', $text, $label);
        $conversation->forceFill(['status' => 'aktif', 'agent_id' => $request->user()->id, 'agent_seen_at' => now()])->save();

        return response()->json(['message' => $msg->toClient()]);
    }

    public function status(Request $request, ChatConversation $conversation)
    {
        $action = $request->validate(['action' => 'required|in:take,finish,reopen'])['action'];
        $label = SiteSettings::get('livechat')['label'];
        [$status, $text] = match ($action) {
            'take' => ['aktif', $label.' bergabung dalam percakapan'],
            'finish' => ['selesai', 'Percakapan diselesaikan petugas'],
            'reopen' => ['aktif', 'Percakapan dibuka kembali oleh petugas'],
        };
        $conversation->addMessage('system', $text);
        $conversation->forceFill(['status' => $status, 'agent_id' => $request->user()->id] + ($action === 'finish' ? ['last_at' => now(), 'last_text' => 'Diselesaikan petugas', 'last_from' => 'system'] : []))->save();
        ActivityLog::record('Live chat '.$conversation->name.': '.['take' => 'diambil', 'finish' => 'diselesaikan', 'reopen' => 'dibuka lagi'][$action]);

        return response()->json(['ok' => true]);
    }

    public function note(Request $request, ChatConversation $conversation)
    {
        $conversation->forceFill(['note' => $request->validate(['note' => 'nullable|string|max:2000'])['note']])->save();

        return response()->json(['ok' => true]);
    }
}
