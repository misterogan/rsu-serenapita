<?php

namespace App\Admin;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Poli;
use App\Models\Role;
use App\Models\Service;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Definisi resource CRUD generik backoffice.
 * Setiap resource: model, judul, kolom tabel, filter, field formulir, dan aturan validasi.
 */
class Resources
{
    public const ILLUSTRATIONS = ['heart', 'child', 'drop', 'phone', 'sun', 'cup', 'cross'];
    public const ICONS = [
        'cross' => 'M10 4h4v6h6v4h-6v6h-4v-6H4v-4h6z', 'bed' => 'M3 18V8m0 6h18v4M7 11h4',
        'lab' => 'M9 3h6M10 3v6l-5 9a2 2 0 0 0 2 3h10a2 2 0 0 0 2-3l-5-9V3', 'scan' => 'M4 4h16v16H4zM9 9h6v6H9z',
        'pill' => 'M8 3h8v5l3 3v10H5V11l3-3z', 'heart' => 'M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.6-7 10-7 10z',
        'knife' => 'M4 20l9-9m2-2 5-5m-6 0 6 6', 'amb' => 'M3 16V8h11l4 4h3v4M7 18a2 2 0 1 0 0-.01M17 18a2 2 0 1 0 0-.01',
        'eye' => 'M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
    ];
    public const COLORS = ['#1F6FB2', '#35A3D8', '#1C3B70', '#2E8B6A', '#C8332E', '#7A4FA8', '#B05C8E', '#D08A2E', '#3D7F8F', '#5B6878', '#8A6D3B', '#4E9A5A'];
    public const LINKS = ['home' => 'Beranda', 'news' => 'Berita', 'doctors' => 'Dokter', 'schedule' => 'Jadwal', 'services' => 'Layanan', 'appointment' => 'Buat Janji', 'contact' => 'Kontak'];

    public static function badge(string $text): string
    {
        $map = ['Terbit' => 'green', 'Aktif' => 'green', 'Hadir' => 'green', 'Draf' => 'grey', 'Selesai' => 'grey', 'Nonaktif' => 'grey',
            'Terjadwal' => 'blue', 'Dikonfirmasi' => 'blue', 'Menunggu' => 'amber', 'Cuti' => 'amber', 'Batal' => 'red'];

        return '<span class="badge b-'.($map[$text] ?? 'grey').'">'.e($text).'</span>';
    }

    public static function toggle(string $res, $model, string $field): string
    {
        return '<form method="post" action="'.route('admin.crud.toggle', [$res, $model->getKey(), $field]).'" class="inline">'.csrf_field()
            .'<label class="switch"><input type="checkbox" onchange="this.form.submit()" '.($model->{$field} ? 'checked' : '').' aria-label="Ubah"><span></span></label></form>';
    }

    public static function avatar(?string $color, string $initials, int $size = 34): string
    {
        return '<div class="av" style="background:'.e($color ?: '#5B6878').';width:'.$size.'px;height:'.$size.'px">'.e($initials).'</div>';
    }

    public static function all(): array
    {
        return [
            'news' => [
                'model' => News::class, 'one' => 'berita', 'title' => 'Berita',
                'desc' => 'Tulis, sunting, dan terbitkan berita yang tampil di halaman Berita dan beranda situs.',
                'with' => ['category'], 'order' => ['published_at', 'desc'], 'search' => ['title', 'excerpt'],
                'filters' => [
                    'status' => ['label' => 'Semua status', 'options' => fn () => ['Terbit' => 'Terbit', 'Draf' => 'Draf', 'Terjadwal' => 'Terjadwal']],
                    'category_id' => ['label' => 'Semua kategori', 'options' => fn () => NewsCategory::orderBy('name')->pluck('name', 'id')->all()],
                ],
                'columns' => [
                    'Judul' => fn ($r) => '<div class="cellrow"><div class="mini-thumb">'.view('partials.cover', ['news' => $r])->render().'</div><div><span class="main">'.e($r->title).'</span><span class="sub">/news/'.e($r->slug).'</span></div></div>',
                    'Kategori' => fn ($r) => e($r->category?->name ?? '—'),
                    'Tanggal' => fn ($r) => $r->published_at->translatedFormat('d M Y'),
                    'Status' => fn ($r) => self::badge($r->status),
                    'Di beranda' => fn ($r) => self::toggle('news', $r, 'featured'),
                ],
                'fields' => [
                    ['name' => 'title', 'label' => 'Judul berita', 'type' => 'text', 'rules' => 'required|string|max:200'],
                    ['name' => 'slug', 'label' => 'Slug alamat', 'type' => 'text', 'half' => true, 'rules' => 'nullable|alpha_dash|max:80', 'hint' => 'Kosongkan untuk dibuat otomatis dari judul.'],
                    ['name' => 'category_id', 'label' => 'Kategori', 'type' => 'select', 'half' => true, 'rules' => 'required|exists:news_categories,id', 'options' => fn () => NewsCategory::orderBy('name')->pluck('name', 'id')->all()],
                    ['name' => 'published_at', 'label' => 'Tanggal terbit', 'type' => 'date', 'half' => true, 'rules' => 'required|date', 'default' => fn () => today()->toDateString()],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'half' => true, 'rules' => 'required|in:Terbit,Draf,Terjadwal', 'options' => fn () => ['Draf' => 'Draf', 'Terbit' => 'Terbit', 'Terjadwal' => 'Terjadwal']],
                    ['name' => 'image', 'label' => 'Foto sampul (opsional)', 'type' => 'image', 'rules' => 'nullable|image|max:3072', 'dir' => 'news', 'hint' => 'JPG, PNG, atau WebP maksimal 3 MB. Jika kosong, memakai ilustrasi di bawah.'],
                    ['name' => 'illustration', 'label' => 'Ilustrasi sampul', 'type' => 'illustration', 'rules' => 'required|in:'.implode(',', self::ILLUSTRATIONS), 'default' => 'heart'],
                    ['name' => 'excerpt', 'label' => 'Ringkasan', 'type' => 'textarea', 'rows' => 3, 'rules' => 'required|string|max:400'],
                    ['name' => 'body', 'label' => 'Isi berita', 'type' => 'textarea', 'rows' => 10, 'rules' => 'nullable|string', 'hint' => 'Pisahkan paragraf dengan satu baris kosong.'],
                    ['name' => 'featured', 'label' => 'Tampilkan di beranda', 'type' => 'toggle', 'desc' => 'Masuk ke bagian Berita terbaru di beranda.'],
                ],
                'before' => function ($model, array &$data, bool $isNew) {
                    $base = Str::slug(($data['slug'] ?? '') ?: $data['title']);
                    $slug = $base;
                    $i = 2;
                    while (News::where('slug', $slug)->where('id', '!=', $model->id ?? 0)->exists()) {
                        $slug = $base.'-'.$i++;
                    }
                    $data['slug'] = $slug;
                    if ($isNew) {
                        $data['author_id'] = auth()->id();
                    }
                },
                'label' => fn ($r) => $r->title,
            ],

            'categories' => [
                'model' => NewsCategory::class, 'one' => 'kategori', 'title' => 'Kategori berita',
                'desc' => 'Kelompok berita yang muncul sebagai filter di halaman Berita.',
                'order' => ['name', 'asc'], 'search' => ['name'], 'withCount' => ['news'],
                'columns' => ['Nama' => fn ($r) => '<span class="main">'.e($r->name).'</span>', 'Jumlah berita' => fn ($r) => $r->news_count],
                'fields' => [['name' => 'name', 'label' => 'Nama kategori', 'type' => 'text', 'rules' => fn ($m) => 'required|string|max:60|unique:news_categories,name,'.($m->id ?? 'NULL')]],
                'canDelete' => fn ($r) => $r->news()->exists() ? 'Kategori ini masih dipakai berita. Pindahkan berita tersebut ke kategori lain dulu.' : null,
                'label' => fn ($r) => $r->name,
            ],

            'slides' => [
                'model' => Slide::class, 'one' => 'slide', 'title' => 'Slider beranda', 'sortable' => true,
                'desc' => 'Slide besar di bagian atas beranda. Urutan di tabel sama dengan urutan tampil.',
                'order' => ['sort', 'asc'], 'search' => ['title', 'text'],
                'columns' => [
                    'Slide' => fn ($r) => '<span class="main">'.e($r->title).'</span><span class="sub">'.e(Str::limit($r->text, 90)).'</span>',
                    'Tombol' => fn ($r) => $r->button ? e($r->button).'<span class="sub">'.e(self::LINKS[$r->link] ?? $r->link).'</span>' : '—',
                    'Tampil' => fn ($r) => self::toggle('slides', $r, 'active'),
                ],
                'fields' => [
                    ['name' => 'title', 'label' => 'Judul besar', 'type' => 'text', 'rules' => 'required|string|max:120'],
                    ['name' => 'text', 'label' => 'Teks pendukung', 'type' => 'textarea', 'rows' => 3, 'rules' => 'nullable|string|max:300'],
                    ['name' => 'tab', 'label' => 'Label tab', 'type' => 'text', 'half' => true, 'rules' => 'nullable|string|max:30'],
                    ['name' => 'button', 'label' => 'Teks tombol', 'type' => 'text', 'half' => true, 'rules' => 'nullable|string|max:40'],
                    ['name' => 'link', 'label' => 'Tujuan tombol', 'type' => 'select', 'rules' => 'nullable|in:'.implode(',', array_keys(self::LINKS)), 'options' => fn () => self::LINKS],
                    ['name' => 'active', 'label' => 'Tampilkan slide', 'type' => 'toggle', 'default' => true],
                ],
                'label' => fn ($r) => $r->title,
            ],

            'services' => [
                'model' => Service::class, 'one' => 'layanan', 'title' => 'Layanan & fasilitas', 'sortable' => true,
                'desc' => 'Daftar layanan penunjang di halaman Layanan.',
                'order' => ['sort', 'asc'], 'search' => ['name', 'description'],
                'columns' => [
                    'Layanan' => fn ($r) => '<div class="cellrow"><svg class="i" style="color:var(--blue)" viewBox="0 0 24 24"><path d="'.(self::ICONS[$r->icon] ?? self::ICONS['cross']).'"/></svg><div><span class="main">'.e($r->name).'</span><span class="sub">'.e(Str::limit($r->description, 90)).'</span></div></div>',
                    'Tampil' => fn ($r) => self::toggle('services', $r, 'show'),
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama layanan', 'type' => 'text', 'rules' => 'required|string|max:80'],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea', 'rows' => 3, 'rules' => 'nullable|string|max:300'],
                    ['name' => 'icon', 'label' => 'Ikon', 'type' => 'icon', 'rules' => 'required|in:'.implode(',', array_keys(self::ICONS)), 'default' => 'cross'],
                    ['name' => 'show', 'label' => 'Tampilkan di situs', 'type' => 'toggle', 'default' => true],
                ],
                'label' => fn ($r) => $r->name,
            ],

            'doctors' => [
                'model' => Doctor::class, 'one' => 'dokter', 'title' => 'Profil dokter', 'simrs' => true, 'noCreate' => true, 'noDelete' => true,
                'desc' => 'Nama, poli, jadwal, dan status dokter diambil dari SIMRS. Di sini Anda hanya mengatur tampilan dokter di situs.',
                'with' => ['poli'], 'order' => ['name', 'asc'], 'search' => ['name'],
                'filters' => [
                    'poli_id' => ['label' => 'Semua poliklinik', 'options' => fn () => Poli::orderBy('sort')->pluck('name', 'id')->all()],
                    'status' => ['label' => 'Semua status', 'options' => fn () => ['Aktif' => 'Aktif', 'Cuti' => 'Cuti', 'Nonaktif' => 'Nonaktif']],
                ],
                'columns' => [
                    'Dokter' => fn ($r) => '<div class="cellrow">'.($r->photo ? '<img class="av" src="'.e(asset('storage/'.$r->photo)).'" alt="">' : self::avatar($r->color, $r->initials())).'<div><span class="main">'.e($r->name).'</span><span class="sub">'.e($r->poli?->name ?? '—').'</span></div></div>',
                    'Hari praktik' => fn ($r) => e(collect($r->schedule ?? [])->map(fn ($s) => mb_substr(Doctor::DAYS[$s[0]], 0, 3))->implode(', ') ?: '—'),
                    'Status SIMRS' => fn ($r) => self::badge($r->status),
                    'Tampil' => fn ($r) => self::toggle('doctors', $r, 'show'),
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama lengkap dan gelar', 'type' => 'readonly', 'value' => fn ($r) => e($r->name)],
                    ['name' => 'poli_id', 'label' => 'Poliklinik', 'type' => 'readonly', 'half' => true, 'value' => fn ($r) => e($r->poli?->name ?? '—')],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'readonly', 'half' => true, 'value' => fn ($r) => self::badge($r->status)],
                    ['name' => 'schedule', 'label' => 'Jadwal praktik', 'type' => 'readonly', 'value' => fn ($r) => collect($r->schedule ?? [])->map(fn ($s) => e(Doctor::DAYS[$s[0]].' · '.$s[1].'–'.$s[2]))->implode('<br>') ?: 'Belum ada jadwal di SIMRS'],
                    ['name' => 'photo', 'label' => 'Foto dokter (opsional)', 'type' => 'image', 'rules' => 'nullable|image|max:2048', 'dir' => 'doctors'],
                    ['name' => 'color', 'label' => 'Warna avatar', 'type' => 'color', 'rules' => 'required|in:'.implode(',', self::COLORS)],
                    ['name' => 'bio', 'label' => 'Profil singkat', 'type' => 'textarea', 'rows' => 3, 'rules' => 'nullable|string|max:600'],
                    ['name' => 'show', 'label' => 'Tampilkan di situs', 'type' => 'toggle', 'desc' => 'Muncul di halaman Dokter, Jadwal, dan formulir janji temu.'],
                ],
                'label' => fn ($r) => $r->name,
            ],

            'clinics' => [
                'model' => Poli::class, 'one' => 'poliklinik', 'title' => 'Poliklinik', 'simrs' => true, 'noCreate' => true, 'noDelete' => true, 'sortable' => true,
                'desc' => 'Daftar poli diambil dari SIMRS. Atur deskripsi, urutan, dan tampilannya di situs.',
                'order' => ['sort', 'asc'], 'search' => ['name', 'description'], 'withCount' => ['doctors'],
                'columns' => [
                    'Poliklinik' => fn ($r) => '<span class="main">'.e($r->name).'</span><span class="sub">'.e(Str::limit($r->description, 90)).'</span>',
                    'Dokter' => fn ($r) => $r->doctors_count,
                    'Unggulan' => fn ($r) => $r->featured ? '<span class="badge b-blue">Unggulan</span>' : '—',
                    'Tampil' => fn ($r) => self::toggle('clinics', $r, 'active'),
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama poliklinik', 'type' => 'readonly', 'value' => fn ($r) => e($r->name)],
                    ['name' => 'description', 'label' => 'Deskripsi di situs', 'type' => 'textarea', 'rows' => 3, 'rules' => 'nullable|string|max:300'],
                    ['name' => 'active', 'label' => 'Tampilkan di situs', 'type' => 'toggle', 'desc' => 'Poli yang disembunyikan tidak muncul di situs dan formulir janji.'],
                    ['name' => 'featured', 'label' => 'Unggulan di beranda', 'type' => 'toggle', 'desc' => 'Tampil sebagai kotak biru di atas daftar poliklinik.'],
                ],
                'after' => function ($model) {
                    if ($model->featured) {
                        Poli::where('id', '!=', $model->id)->update(['featured' => false]);
                    }
                },
                'label' => fn ($r) => $r->name,
            ],

            'appointments' => [
                'model' => Appointment::class, 'one' => 'janji temu', 'title' => 'Janji temu', 'simrs' => true, 'noCreate' => true, 'noDelete' => true, 'noEdit' => true,
                'desc' => 'Pantauan janji temu yang dibuat di situs dan diteruskan ke SIMRS. Konfirmasi, kehadiran, dan pembatalan dilakukan di SIMRS.',
                'with' => ['doctor.poli'], 'order' => ['date', 'desc'], 'search' => ['code', 'name', 'phone', 'simrs_booking_code'],
                'filters' => [
                    'status' => ['label' => 'Semua status', 'options' => fn () => array_combine(Appointment::STATUSES, Appointment::STATUSES)],
                    'date' => ['label' => 'Tanggal', 'type' => 'date'],
                ],
                'columns' => [
                    'Kode' => fn ($r) => '<span class="main">'.e($r->code).'</span><span class="sub">'.e($r->simrs_booking_code ?: '').'</span>',
                    'Pasien' => fn ($r) => '<span class="main">'.e($r->name).'</span><span class="sub">'.e($r->phone).($r->medical_record ? ' · '.e($r->medical_record) : '').'</span>',
                    'Dokter' => fn ($r) => e($r->doctor?->name ?? '—').'<span class="sub">'.e($r->doctor?->poli?->name ?? '').'</span>',
                    'Waktu' => fn ($r) => $r->date->translatedFormat('D, d M Y').'<span class="sub">Pukul '.e($r->time).($r->queue_number ? ' · Antrean '.e($r->queue_number) : '').'</span>',
                    'Bayar' => fn ($r) => e($r->payment),
                    'Status SIMRS' => fn ($r) => self::badge($r->status),
                    'Pengiriman' => fn ($r) => $r->simrs_sent_at ? '<span class="badge b-green">Terkirim</span>' : '<span class="badge b-red" title="'.e($r->simrs_error).'">Gagal</span>'
                        .(! $r->simrs_sent_at ? '<form method="post" class="inline" action="'.route('admin.appointments.resend', $r).'">'.csrf_field().'<button class="btn sm" style="margin-left:6px">Kirim ulang</button></form>' : ''),
                ],
                'fields' => [],
                'label' => fn ($r) => $r->code,
            ],

            'users' => [
                'model' => User::class, 'one' => 'pengguna', 'title' => 'Pengguna',
                'desc' => 'Akun petugas yang dapat masuk ke backoffice. Hak akses diatur per peran.',
                'with' => ['role'], 'order' => ['name', 'asc'], 'search' => ['name', 'email'],
                'filters' => ['role_id' => ['label' => 'Semua peran', 'options' => fn () => Role::orderBy('name')->pluck('name', 'id')->all()]],
                'columns' => [
                    'Pengguna' => fn ($r) => '<div class="cellrow">'.self::avatar(self::COLORS[crc32($r->email) % count(self::COLORS)], $r->initials()).'<div><span class="main">'.e($r->name).'</span><span class="sub">'.e($r->email).'</span></div></div>',
                    'Peran' => fn ($r) => e($r->role?->name ?? '—'),
                    'Terakhir masuk' => fn ($r) => $r->last_login_at?->translatedFormat('d M Y H.i') ?? 'Belum pernah',
                    'Aktif' => fn ($r) => $r->id === auth()->id() ? self::badge('Aktif') : self::toggle('users', $r, 'is_active'),
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'rules' => 'required|string|max:100'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'rules' => fn ($m) => 'required|email|max:150|unique:users,email,'.($m->id ?? 'NULL')],
                    ['name' => 'role_id', 'label' => 'Peran', 'type' => 'select', 'half' => true, 'rules' => 'required|exists:roles,id', 'options' => fn () => Role::orderBy('name')->pluck('name', 'id')->all()],
                    ['name' => 'password', 'label' => 'Kata sandi', 'type' => 'password', 'half' => true, 'rules' => fn ($m) => ($m->exists ? 'nullable' : 'required').'|string|min:8', 'hint' => 'Minimal 8 karakter. Kosongkan saat mengubah jika tidak diganti.'],
                    ['name' => 'is_active', 'label' => 'Akun aktif', 'type' => 'toggle', 'default' => true, 'desc' => 'Akun nonaktif tidak dapat masuk.'],
                ],
                'before' => function ($model, array &$data, bool $isNew) {
                    if (empty($data['password'])) {
                        unset($data['password']);
                    }
                    if ($model->id === auth()->id()) {
                        $data['is_active'] = true;
                        $data['role_id'] = $model->role_id;
                    }
                },
                'canDelete' => fn ($r) => $r->id === auth()->id() ? 'Anda tidak dapat menghapus akun yang sedang dipakai.' : null,
                'label' => fn ($r) => $r->name,
            ],
        ];
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
