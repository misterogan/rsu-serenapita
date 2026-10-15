<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\Faq;
use App\Models\Feedback;
use App\Models\GalleryItem;
use App\Models\Guide;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Halaman informasi: Tentang Kami, Galeri, Panduan Pasien & FAQ, Pengaduan & Saran. */
class InfoController extends Controller
{
    protected function guard(string $section): void
    {
        abort_unless(SiteSettings::get('sections')[$section] ?? true, 404);
    }

    public function about()
    {
        $a = SiteSettings::get('about');
        $lines = fn ($v) => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $v))));

        return view('site.about', [
            'a' => $a,
            'history' => array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', (string) $a['history'])))),
            'mission' => $lines($a['mission']), 'values' => $lines($a['values']), 'awards' => $lines($a['awards']),
            'img' => fn ($k) => ! empty($a[$k]) ? asset('storage/'.$a[$k]) : null,
        ]);
    }

    public function gallery(Request $request)
    {
        $this->guard('gallery');
        $q = GalleryItem::where('show', true)->orderBy('sort')->orderByDesc('id');
        if ($t = $request->query('type')) {
            $q->where('type', $t === 'video' ? 'video' : 'photo');
        }
        if ($al = $request->query('album')) {
            $q->where('album', $al);
        }

        return view('site.gallery', [
            'items' => $q->get(),
            'albums' => GalleryItem::where('show', true)->whereNotNull('album')->distinct()->orderBy('album')->pluck('album'),
        ]);
    }

    public function guide()
    {
        $this->guard('guide');

        return view('site.guide', [
            'guides' => Guide::where('show', true)->orderBy('sort')->get(),
            'faqs' => Faq::where('show', true)->orderBy('sort')->get(),
        ]);
    }

    public function feedback()
    {
        $this->guard('feedback');

        return view('site.feedback', ['sent' => session('feedback_sent'), 'types' => Feedback::TYPES, 'categories' => Feedback::CATEGORIES]);
    }

    public function storeFeedback(Request $request)
    {
        $this->guard('feedback');
        if (filled($request->input('website'))) {
            return redirect()->route('feedback'); // perangkap robot
        }
        $data = $request->validate([
            'type' => 'required|in:'.implode(',', Feedback::TYPES),
            'category' => 'required|in:'.implode(',', Feedback::CATEGORIES),
            'unit' => 'nullable|string|max:100',
            'name' => 'required|string|min:3|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:150',
            'incident_date' => 'nullable|date|before_or_equal:today',
            'message' => 'required|string|min:15|max:3000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:3072',
            'consent' => 'accepted',
        ], ['message.min' => 'Mohon jelaskan sedikit lebih lengkap, minimal 15 karakter.'], [
            'type' => 'jenis', 'category' => 'kategori', 'unit' => 'unit atau ruangan', 'name' => 'nama', 'phone' => 'nomor HP',
            'incident_date' => 'tanggal kejadian', 'message' => 'isi laporan', 'attachment' => 'lampiran', 'consent' => 'persetujuan',
        ]);
        $phone = ChatConversation::normalizePhone($data['phone']);
        if (! preg_match('/^08[1-9]\d{6,11}$/', $phone)) {
            throw ValidationException::withMessages(['phone' => 'Nomor HP tidak valid. Contoh: 081234567890.']);
        }
        do {
            $code = 'ADU-'.strtoupper(Str::random(6));
        } while (Feedback::where('code', $code)->exists());

        $file = $request->hasFile('attachment') ? $request->file('attachment')->store('feedback', 'local') : null;
        Feedback::create(['code' => $code, 'type' => $data['type'], 'category' => $data['category'], 'unit' => $data['unit'] ?? null,
            'name' => $data['name'], 'phone' => $phone, 'email' => $data['email'] ?? null, 'incident_date' => $data['incident_date'] ?? null,
            'message' => $data['message'], 'attachment' => $file, 'status' => 'Baru']);

        return redirect()->route('feedback')->with('feedback_sent', $code);
    }

    public function feedbackStatus(Request $request)
    {
        $this->guard('feedback');
        $code = strtoupper(trim((string) $request->query('code')));
        $phone = ChatConversation::normalizePhone((string) $request->query('phone'));
        $ticket = ($code && $phone) ? Feedback::where('code', $code)->where('phone', $phone)->first() : null;

        return view('site.feedback', [
            'sent' => null, 'types' => Feedback::TYPES, 'categories' => Feedback::CATEGORIES,
            'ticket' => $ticket, 'checked' => $code !== '' || $request->has('phone'),
        ]);
    }
}
