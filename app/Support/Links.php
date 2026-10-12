<?php

namespace App\Support;

class Links
{
    public const ROUTES = ['home' => 'home', 'news' => 'news.index', 'doctors' => 'doctors', 'schedule' => 'schedule', 'services' => 'services', 'appointment' => 'appointment', 'contact' => 'contact'];

    /** Kunci lama (bahasa Indonesia) tetap dikenali agar data lama tidak rusak. */
    public const LEGACY = ['beranda' => 'home', 'berita' => 'news', 'dokter' => 'doctors', 'jadwal' => 'schedule', 'layanan' => 'services', 'janji' => 'appointment', 'kontak' => 'contact'];

    public static function key(?string $key): ?string
    {
        return self::LEGACY[$key] ?? $key;
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
