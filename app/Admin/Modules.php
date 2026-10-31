<?php

namespace App\Admin;

/** Daftar menu backoffice. Kunci dipakai untuk hak akses per peran. */
class Modules
{
    public const GROUPS = [
        'Utama' => ['dashboard' => 'Dasbor', 'livechat' => 'Live chat', 'feedback' => 'Pengaduan & saran'],
        'Konten situs' => ['news' => 'Berita', 'categories' => 'Kategori berita', 'slides' => 'Slider beranda', 'info' => 'Kotak info & jam', 'services' => 'Layanan & fasilitas'],
        'Pelayanan medis' => ['doctors' => 'Profil dokter', 'clinics' => 'Poliklinik', 'schedule' => 'Jadwal dokter', 'leaves' => 'Cuti dokter'],
        'Pendaftaran' => ['appointments' => 'Janji temu', 'appointment-form' => 'Formulir janji temu'],
        'Farmasi' => ['stock' => 'Input stok harian', 'stock-report' => 'Rekap persediaan', 'stock-compare' => 'Cocokkan SIMRS', 'medicines' => 'Data obat', 'bmhp' => 'Data BMHP'],
        'Karier' => ['jobs' => 'Lowongan kerja', 'applications' => 'Pelamar'],
        'Informasi situs' => ['about' => 'Tentang kami', 'gallery' => 'Galeri', 'guides' => 'Panduan pasien', 'faqs' => 'FAQ'],
        'Tampilan situs' => ['menu' => 'Menu & tampilan', 'chatbot' => 'Chatbot Serena', 'profile' => 'Profil & kontak'],
        'Sistem' => ['simrs' => 'Integrasi SIMRS', 'users' => 'Pengguna', 'roles' => 'Hak akses', 'logs' => 'Log aktivitas'],
    ];

    public const ICONS = [
        'dashboard' => 'M4 4h7v7H4zM13 4h7v4h-7zM13 10h7v10h-7zM4 13h7v7H4z',
        'livechat' => 'M4 5h12v8H9l-5 4zM20 9v9l-4-3h-6v-2',
        'news' => 'M5 4h11v16H5zM16 8h3v10a2 2 0 0 1-2 2M8 8h5M8 12h5M8 16h3',
        'categories' => 'M4 4h7l9 9-7 7-9-9zM8 8h.01',
        'slides' => 'M3 5h18v12H3zM8 21h8M10 9l4 2-4 2z',
        'info' => 'M4 4h16v16H4zM4 10h16M10 10v10',
        'services' => 'M10 4h4v6h6v4h-6v6h-4v-6H4v-4h6z',
        'doctors' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21c0-4 4-6 8-6s8 2 8 6',
        'clinics' => 'M4 21V8l8-5 8 5v13M9 21v-6h6v6',
        'schedule' => 'M4 6h16v14H4zM4 10h16M8 3v5M16 3v5',
        'appointments' => 'M4 6h16v14H4zM4 10h16M9 15l2 2 4-4',
        'appointment-form' => 'M6 3h12v18H6zM9 8h6M9 12h6M9 16h3',
        'menu' => 'M4 6h16M4 12h16M4 18h10',
        'jobs' => 'M4 8h16v11H4zM9 8V5h6v3M4 13h16',
        'management' => 'M4 20V10M10 20V4M16 20v-7M22 20H2',
        'about' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 11v6M12 7.5h.01',
        'gallery' => 'M4 5h16v14H4zM4 15l4-4 4 4 3-3 5 5M15.5 9.5h.01',
        'guides' => 'M5 4h11l3 3v13H5zM9 9h6M9 13h6M9 17h4',
        'faqs' => 'M8 9a4 4 0 1 1 5.5 3.7c-.9.4-1.5 1.2-1.5 2.2V16M12 19.5h.01',
        'feedback' => 'M4 5h16v11H8l-4 4zM8 9h8M8 12h5',
        'leaves' => 'M5 5h14v15H5zM5 9h14M9 3v4M15 3v4M9 14l6 0',
        'applications' => 'M9 4h6v3H9zM7 5H5v16h14V5h-2M8 12h8M8 16h5',
        'chatbot' => 'M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z',
        'profile' => 'M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11zM12 12a2 2 0 1 0 0-4 2 2 0 0 0 0 4z',
        'simrs' => 'M4 8h13M14 5l3 3-3 3M20 16H7M10 13l-3 3 3 3',
        'users' => 'M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM3 20c0-3 3-5 6-5s6 2 6 5M16 5a3 3 0 0 1 0 6M21 20c0-2-1-4-3-4.5',
        'roles' => 'M6 11V8a6 6 0 0 1 12 0v3M5 11h14v10H5z',
        'logs' => 'M12 7v5l3 2M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5',
        'stock' => 'M8 3h8v4l2 3v11H6V10l2-3zM6 13h12M12 15v4M10 17h4',
        'stock-report' => 'M5 4h14v16H5zM9 16v-3M12 16V9M15 16v-5',
        'medicines' => 'M10.5 20.5a5 5 0 0 1-7-7l7-7a5 5 0 0 1 7 7zM7 10l7 7',
        'bmhp' => 'M4 8l8-4 8 4-8 4zM4 8v8l8 4 8-4V8M12 12v8',
        'stock-compare' => 'M7 4v13M3 13l4 4 4-4M17 20V7M13 11l4-4 4 4',
    ];

    public static function all(): array
    {
        return array_merge(...array_values(self::GROUPS));
    }

    public static function name(string $key): string
    {
        return self::all()[$key] ?? (self::EXTRA[$key] ?? $key);
    }

    public static function group(string $key): string
    {
        foreach (self::GROUPS as $g => $mods) {
            if (isset($mods[$key])) {
                return $g;
            }
        }

        return $key === 'management' ? 'Manajemen' : '';
    }

    /** Menu di luar daftar hak akses biasa. */
    public const EXTRA = ['management' => 'Dasbor manajemen'];
}
