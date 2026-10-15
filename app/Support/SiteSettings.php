<?php

namespace App\Support;

use App\Models\Phone;
use App\Models\Setting;
use Illuminate\Support\Collection;

/** Semua pengaturan situs beserta nilai bawaannya. */
class SiteSettings
{
    public const DEFAULTS = [
        'profile' => [
            'name' => 'RSU Serenapita Sidikalang',
            'short' => 'Sidikalang, Dairi',
            'logo' => null,
            'logo_dark' => null,
            'share_image' => null,
            'street' => 'Jl. Sisingamangaraja No.91',
            'district' => 'Kec. Sidikalang',
            'city' => 'Kabupaten Dairi',
            'province' => 'Sumatera Utara',
            'zip' => '22218',
            'maps' => 'https://www.google.com/maps/search/?api=1&query=Rumah+Sakit+Umum+Serenapita+Sidikalang',
            'email' => 'info@rsuserenapita.co.id',
            'counter_hours' => 'Senin – Jumat 07.30 – 15.00, Sabtu 07.30 – 12.00',
            'visiting_hours' => '11.00 – 13.00 dan 17.00 – 20.00',
            'footer' => 'Rumah sakit umum yang melayani masyarakat Kabupaten Dairi dan sekitarnya.',
        ],
        'sections' => [
            'slider' => true, 'boxes' => true, 'news' => true, 'poli_side' => true, 'book_side' => true,
            'topbar' => true, 'chatbot' => true, 'loader' => true, 'footer_news' => true, 'footer_links' => true, 'dark_mode' => false, 'careers' => true,
        ],
        'boxes' => [
            ['title' => 'Gawat darurat', 'text' => 'IGD buka 24 jam setiap hari, termasuk hari libur.', 'button' => 'Lokasi IGD', 'link' => 'contact', 'show_phone' => true],
            ['title' => 'Jadwal dokter', 'text' => 'Lihat jam praktik dokter spesialis dan dokter umum di setiap poliklinik.', 'button' => 'Buka jadwal', 'link' => 'schedule', 'show_phone' => false],
            ['title' => 'Jam pelayanan', 'text' => '', 'button' => '', 'link' => '', 'show_phone' => false],
        ],
        'hours' => [
            ['label' => 'Poliklinik Senin – Jumat', 'value' => '08.00 – 16.00'],
            ['label' => 'Poliklinik Sabtu', 'value' => '08.00 – 13.00'],
            ['label' => 'Jam besuk siang', 'value' => '11.00 – 13.00'],
            ['label' => 'Jam besuk sore', 'value' => '17.00 – 20.00'],
        ],
        'booking' => [
            'online' => true,
            'require_nik' => true,
            'require_bpjs' => true,
            'rujukan' => true,
            'consent' => 'Saya menyetujui data saya digunakan RSU Serenapita untuk keperluan pendaftaran dan pelayanan kesehatan.',
            'note' => 'Datang ke loket pendaftaran paling lambat 15 menit sebelum jam praktik dan sebutkan kode janji Anda.',
            'interval' => 30,
            'slot_quota' => 3,
        ],
        'chatbot' => [
            'enabled' => true,
            'name' => 'Serena',
            'greeting' => 'Halo, saya Serena. Saya bisa bantu informasi jadwal dokter, poliklinik, cara daftar BPJS, jam besuk, dan lokasi rumah sakit.',
            'quick' => ['Jadwal dokter anak?', 'Bagaimana cara daftar BPJS?', 'Jam besuk kapan?'],
            'faq' => "parkir, kendaraan, motor, mobil: Parkir tersedia di halaman depan rumah sakit dan gratis untuk pasien.\nkantin, makan, minum: Kantin buka setiap hari pukul 07.00 – 19.00 di samping lobi.\nwifi, internet: Wi-Fi gratis tersedia di ruang tunggu. Tanyakan kata sandinya di loket informasi.\nhasil lab, hasil laboratorium: Hasil laboratorium bisa diambil di loket laboratorium dengan menunjukkan bukti pemeriksaan.",
            'fallback' => '',
            'emergency' => 'Untuk keadaan darurat, segera ke IGD yang buka 24 jam.',
        ],
        'livechat' => [
            'enabled' => true,
            'label' => 'Petugas RSU Serenapita',
            'hours' => '07.00 – 21.00',
            'welcome' => 'Halo {nama}, terima kasih sudah menghubungi RSU Serenapita. Petugas kami akan segera membalas.',
            'canned' => [
                'Baik, kami bantu cek terlebih dahulu ya.',
                'Jadwal dokter terbaru bisa dilihat di menu Jadwal pada situs kami.',
                'Untuk membuat janji, silakan gunakan menu Buat Janji di situs.',
                'Untuk keadaan darurat, segera datang ke IGD yang buka 24 jam.',
                'Terima kasih sudah menghubungi kami. Semoga lekas sehat.',
            ],
        ],
        'simrs' => [
            'source' => 'manual',  // manual | simrs : sumber data dokter, poli, jadwal, cuti
            'mode' => 'poll',      // webhook | poll
            'interval' => 2,       // menit
            'send_booking' => true,
            'fallback' => true,
            'status' => 'Belum diuji',
            'last_sync' => null,
        ],
    ];

    public static function get(string $key): array
    {
        return Setting::get($key, self::DEFAULTS[$key] ?? []);
    }

    public static function put(string $key, array $value): void
    {
        Setting::put($key, $value);
    }

    public static function profile(): array
    {
        return self::get('profile');
    }

    public static function address(?array $p = null): string
    {
        $p ??= self::profile();
        $last = trim(($p['province'] ?? '').' '.($p['zip'] ?? ''));

        return collect([$p['street'] ?? '', $p['district'] ?? '', $p['city'] ?? '', $last])->filter()->implode(', ');
    }

    public static function shortAddress(?array $p = null): string
    {
        $p ??= self::profile();

        return collect([$p['street'] ?? '', preg_replace('/^Kec\.\s*/', '', $p['district'] ?? ''), $p['city'] ?? ''])->filter()->implode(', ');
    }

    public static function logoUrl(bool $dark = false): ?string
    {
        $p = self::profile();
        $path = $dark ? ($p['logo_dark'] ?: $p['logo']) : $p['logo'];

        return $path ? asset('storage/'.$path) : null;
    }

    /** Gambar pratinjau saat link dibagikan (WhatsApp, Facebook, dll). */
    public static function shareImageUrl(): string
    {
        $p = self::profile();

        return ! empty($p['share_image']) ? asset('storage/'.$p['share_image']) : asset('images/og-default.jpg');
    }

    /** @return Collection<int, Phone> */
    public static function phones(?string $where = null): Collection
    {
        $all = Phone::query()->orderBy('sort')->get();

        return $where ? $all->where('show_'.$where, true)->values() : $all;
    }

    public static function emergencyPhone(): ?Phone
    {
        return self::phones('igd')->first() ?? self::phones()->first();
    }
}
