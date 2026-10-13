<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Resources;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Menu;
use App\Models\Phone;
use App\Services\Chatbot;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Halaman pengaturan konten: kotak info, menu & tampilan, profil & kontak, formulir janji, chatbot. */
class ContentController extends Controller
{
    public const SECTIONS = [
        'Bagian beranda' => [
            'slider' => ['Slider hero', 'Slide besar di paling atas beranda.'],
            'boxes' => ['Kotak info', 'Gawat darurat, jadwal dokter, dan jam pelayanan.'],
            'news' => ['Berita terbaru', 'Kolom berita di beranda.'],
            'poli_side' => ['Daftar poliklinik', 'Sidebar poli di samping berita.'],
            'book_side' => ['Kotak buat janji', 'Kontak singkat untuk membuat janji di sidebar.'],
        ],
        'Elemen global' => [
            'topbar' => ['Bar informasi atas', 'Alamat dan nomor telepon di atas header.'],
            'chatbot' => ['Tombol chat', 'Tombol chat Serena dan live chat di pojok kanan bawah.'],
            'loader' => ['Loading perpindahan halaman', 'Logo detak jantung dan blur saat pindah menu.'],
            'footer_news' => ['Berita di footer', 'Tiga berita terakhir di footer.'],
            'footer_links' => ['Tautan cepat di footer', 'Daftar tautan pintas di footer.'],
        ],
    ];

    /* ---------- Kotak info & jam ---------- */
    public function info()
    {
        return view('admin.content.info', ['boxes' => SiteSettings::get('boxes'), 'hours' => SiteSettings::get('hours'), 'links' => Resources::LINKS]);
    }

    public function saveInfo(Request $request)
    {
        $data = $request->validate([
            'boxes' => 'required|array|size:3',
            'boxes.*.title' => 'required|string|max:60',
            'boxes.*.text' => 'nullable|string|max:300',
            'boxes.*.button' => 'nullable|string|max:40',
            'boxes.*.link' => ['nullable', Rule::in(array_keys(Resources::LINKS))],
            'hours' => 'nullable|array|max:12',
            'hours.*.label' => 'nullable|string|max:60',
            'hours.*.value' => 'nullable|string|max:40',
        ]);
        $boxes = collect($data['boxes'])->values()->map(fn ($b, $i) => [
            'title' => $b['title'], 'text' => $b['text'] ?? '', 'button' => $b['button'] ?? '', 'link' => $b['link'] ?? '',
            'show_phone' => $i === 0 && $request->boolean('show_phone'),
        ])->all();
        $hours = collect($data['hours'] ?? [])->filter(fn ($h) => ($h['label'] ?? '') !== '' || ($h['value'] ?? '') !== '')
            ->map(fn ($h) => ['label' => $h['label'] ?? '', 'value' => $h['value'] ?? ''])->values()->all();
        SiteSettings::put('boxes', $boxes);
        SiteSettings::put('hours', $hours);
        ActivityLog::record('Memperbarui kotak info beranda');

        return back()->with('ok', 'Kotak info disimpan.');
    }

    /* ---------- Menu & tampilan ---------- */
    public function menu()
    {
        return view('admin.content.menu', ['menus' => Menu::orderBy('sort')->get(), 'sections' => SiteSettings::get('sections'), 'groups' => self::SECTIONS]);
    }

    public function saveMenu(Request $request)
    {
        $data = $request->validate(['menus' => 'required|array', 'menus.*.label' => 'required|string|max:30', 'order' => 'nullable|string']);
        $order = array_values(array_filter(explode(',', (string) ($data['order'] ?? ''))));
        DB::transaction(function () use ($data, $order, $request) {
            foreach (Menu::all() as $m) {
                if (! isset($data['menus'][$m->id])) {
                    continue;
                }
                $pos = array_search((string) $m->id, $order, true);
                $m->update([
                    'label' => $data['menus'][$m->id]['label'],
                    'visible' => $m->locked ? true : $request->boolean("menus.{$m->id}.visible"),
                    'sort' => $pos === false ? $m->sort : $pos + 1,
                ]);
            }
            $sections = [];
            foreach (self::SECTIONS as $group) {
                foreach (array_keys($group) as $k) {
                    $sections[$k] = $request->boolean("sections.$k");
                }
            }
            SiteSettings::put('sections', $sections);
        });
        ActivityLog::record('Memperbarui menu & tampilan situs');

        return back()->with('ok', 'Menu dan tampilan disimpan.');
    }

    /* ---------- Profil, logo & kontak ---------- */
    public function profile()
    {
        return view('admin.content.profile', ['p' => SiteSettings::profile(), 'phones' => Phone::orderBy('sort')->get()]);
    }

    public function saveProfile(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'short' => 'nullable|string|max:60',
            'street' => 'required|string|max:150',
            'district' => 'nullable|string|max:80',
            'city' => 'required|string|max:80',
            'province' => 'nullable|string|max:80',
            'zip' => 'nullable|string|max:10',
            'maps' => 'nullable|url:https|max:500',
            'email' => 'nullable|email|max:120',
            'counter_hours' => 'nullable|string|max:120',
            'visiting_hours' => 'nullable|string|max:120',
            'footer' => 'nullable|string|max:300',
            'logo' => 'nullable|file|mimes:png,jpg,jpeg,webp,svg|max:1024',
            'logo_dark' => 'nullable|file|mimes:png,jpg,jpeg,webp,svg|max:1024',
            'share_image' => 'nullable|image|mimes:jpg,jpeg,png|max:600',
            'phones' => 'required|array|min:1|max:10',
            'phones.*.label' => 'required|string|max:40',
            'phones.*.number' => ['required', 'string', 'max:20', 'regex:/^[0-9+()\-\s.]{6,20}$/'],
            'phones.*.kind' => 'required|in:tel,wa',
        ], [
            'phones.*.number.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, +, -, atau tanda kurung (6–20 karakter).',
        ], ['phones.*.label' => 'keterangan nomor', 'phones.*.number' => 'nomor telepon']);

        $phones = collect($request->input('phones'))->values();
        if (! $phones->contains(fn ($ph) => ! empty($ph['show_igd']))) {
            return back()->withInput()->withErrors(['phones' => 'Centang minimal satu nomor untuk Kotak IGD.']);
        }

        $p = SiteSettings::profile();
        foreach (['logo', 'logo_dark', 'share_image'] as $k) {
            if ($request->boolean($k.'_remove') && ! empty($p[$k])) {
                Storage::disk('public')->delete($p[$k]);
                $p[$k] = null;
            }
            if ($request->hasFile($k)) {
                if (! empty($p[$k])) {
                    Storage::disk('public')->delete($p[$k]);
                }
                $p[$k] = $request->file($k)->store('branding', 'public');
            }
        }
        unset($data['logo'], $data['logo_dark'], $data['share_image'], $data['phones']);
        SiteSettings::put('profile', array_merge($p, $data));

        DB::transaction(function () use ($phones) {
            Phone::query()->delete();
            foreach ($phones as $i => $ph) {
                Phone::create([
                    'label' => $ph['label'], 'number' => $ph['number'], 'kind' => $ph['kind'], 'sort' => $i,
                    'show_top' => ! empty($ph['show_top']), 'show_igd' => ! empty($ph['show_igd']),
                    'show_contact' => ! empty($ph['show_contact']), 'show_footer' => ! empty($ph['show_footer']),
                ]);
            }
        });
        ActivityLog::record('Memperbarui profil, logo & kontak');

        return back()->with('ok', 'Profil disimpan dan diterapkan di seluruh situs.');
    }

    /* ---------- Formulir janji temu ---------- */
    public function booking()
    {
        return view('admin.content.booking', ['b' => SiteSettings::get('booking')]);
    }

    public function saveBooking(Request $request)
    {
        $data = $request->validate(['consent' => 'required|string|max:400', 'note' => 'nullable|string|max:400']);
        SiteSettings::put('booking', array_merge(SiteSettings::get('booking'), $data, [
            'online' => $request->boolean('online'), 'require_nik' => $request->boolean('require_nik'),
            'require_bpjs' => $request->boolean('require_bpjs'), 'rujukan' => $request->boolean('rujukan'),
        ]));
        ActivityLog::record('Memperbarui formulir janji temu');

        return back()->with('ok', 'Formulir janji temu disimpan.');
    }

    /* ---------- Chatbot ---------- */
    public function chatbot()
    {
        return view('admin.content.chatbot', ['b' => SiteSettings::get('chatbot')]);
    }

    public function saveChatbot(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:30', 'greeting' => 'required|string|max:400', 'quick' => 'nullable|string|max:600',
            'faq' => 'nullable|string|max:8000', 'emergency' => 'required|string|max:300', 'fallback' => 'nullable|string|max:600',
        ]);
        $data['quick'] = array_values(array_filter(array_map('trim', preg_split('/\R/', $data['quick'] ?? ''))));
        $data['enabled'] = $request->boolean('enabled');
        $data['fallback'] ??= '';
        SiteSettings::put('chatbot', $data);
        ActivityLog::record('Memperbarui pengaturan chatbot');

        return back()->with('ok', 'Chatbot disimpan.');
    }

    public function testChatbot(Request $request, Chatbot $bot)
    {
        $q = $request->validate(['message' => 'required|string|max:500'])['message'];

        return response()->json(['answer' => $bot->answer($q)]);
    }
}
