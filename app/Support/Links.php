<?php

namespace App\Support;

use App\Models\Doctor;
use App\Models\Menu;

class Links
{
    public const ROUTES = ['home' => 'home', 'news' => 'news.index', 'doctors' => 'doctors', 'schedule' => 'schedule', 'services' => 'services', 'appointment' => 'appointment', 'contact' => 'contact', 'about' => 'about'];

    /** Kunci lama (bahasa Indonesia) tetap dikenali agar data lama tidak rusak. */
    public const LEGACY = ['beranda' => 'home', 'berita' => 'news', 'dokter' => 'doctors', 'jadwal' => 'schedule', 'layanan' => 'services', 'janji' => 'appointment', 'kontak' => 'contact'];

    public static function key(?string $key): ?string
    {
        return self::LEGACY[$key] ?? $key;
    }

    /**
     * Apakah halaman boleh dibuka pengunjung.
     * Halaman yang menunya disembunyikan di "Menu & tampilan" dianggap nonaktif:
     * tautan dan tombol ke halaman itu disembunyikan, dan alamatnya menampilkan 404.
     * Buat Janji juga nonaktif bila tidak ada satu pun dokter yang tampil.
     */
    public static function enabled(?string $key): bool
    {
        $key = self::key($key);
        if (! $key || $key === 'home') {
            return true;
        }
        $state = once(fn () => [
            'menus' => Menu::query()->pluck('visible', 'route')->mapWithKeys(fn ($v, $k) => [self::key($k) => (bool) $v])->all(),
            'doctors' => Doctor::public()->exists(),
        ]);
        $visible = $state['menus'][$key] ?? true;
        if ($key === 'appointment') {
            return $visible && $state['doctors'];
        }

        return $visible;
    }

    public static function url(?string $key): string
    {
        $key = self::key($key);

        return isset(self::ROUTES[$key]) ? route(self::ROUTES[$key]) : url('/');
    }

    public static function active(string $key): bool
    {
        $key = self::key($key);
        $route = self::ROUTES[$key] ?? null;

        return $route && (request()->routeIs($route) || ($key === 'news' && request()->routeIs('news.*')) || ($key === 'appointment' && request()->routeIs('appointment.*')));
    }
}
