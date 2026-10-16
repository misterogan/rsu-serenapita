<?php

namespace App\Admin;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Faq;
use App\Models\Feedback;
use App\Models\GalleryItem;
use App\Models\Guide;
use App\Models\JobApplication;
use App\Models\JobOpening;
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
            'Terjadwal' => 'blue', 'Dikonfirmasi' => 'blue', 'Menunggu' => 'amber', 'Cuti' => 'amber', 'Batal' => 'red',
            'Dibuka' => 'green', 'Ditutup' => 'grey', 'Baru' => 'blue', 'Diproses' => 'amber', 'Wawancara' => 'blue', 'Diterima' => 'green', 'Ditolak' => 'red'];

        return '<span class="badge b-'.($map[$text] ?? 'grey').'">'.e($text).'</span>';
    }

    public static function toggle(string $res, $model, string $field): string
    {
        return '<form method="post" action="'.route('admin.crud.toggle', [$res, $model->getKey(), $field]).'" class="inline">'.csrf_field()
            .'<label class="switch"><input type="checkbox" onchange="this.form.submit()" '.($model->{$field} ? 'checked' : '').' aria-label="Ubah"><span></span></label></form>';
    }

    /** Rapikan isian jadwal dari formulir menjadi [[hari, "08.00", "12.00"], ...]. */
    public static function cleanSchedule(array $rows): array
    {
        $out = [];
        foreach ($rows as $i => $row) {
            $day = $row['day'] ?? '';
            $start = str_replace(':', '.', trim((string) ($row['start'] ?? '')));
            $end = str_replace(':', '.', trim((string) ($row['end'] ?? '')));
            if ($day === '' && $start === '' && $end === '') {
                continue;
            }
            $ok = preg_match('/^\d{2}\.\d{2}$/', $start) && preg_match('/^\d{2}\.\d{2}$/', $end) && ctype_digit((string) $day) && (int) $day <= 6;
            if (! $ok || $start >= $end) {
                throw \Illuminate\Validation\ValidationException::withMessages(['schedule' => 'Jadwal baris '.($i + 1).' belum benar. Pilih hari, lalu isi jam mulai yang lebih awal dari jam selesai.']);
            }
            $out[] = [(int) $day, $start, $end];
        }
        usort($out, fn ($a, $b) => [($a[0] + 6) % 7, $a[1]] <=> [($b[0] + 6) % 7, $b[1]]);

        return $out;
    }

    public static function avatar(?string $color, string $initials, int $size = 34): string
    {
        return '<div class="av" style="background:'.e($color ?: '#5B6878').';width:'.$size.'px;height:'.$size.'px">'.e($initials).'</div>';
    }

    public static function all(): array
    {
        $manual = \App\Support\DataSource::manual();
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
                    'Slide' => fn ($r) => '<div class="cellrow"><div class="mini-thumb" style="background:#1C3B70">'.($r->image ? '<img src="'.e(asset('storage/'.$r->image)).'" alt="">' : '').'</div><div><span class="main">'.e($r->title).'</span><span class="sub">'.e(Str::limit($r->text, 90)).'</span></div></div>',
                    'Tombol' => fn ($r) => $r->button ? e($r->button).'<span class="sub">'.e(self::LINKS[$r->link] ?? $r->link).'</span>' : '—',
                    'Tampil' => fn ($r) => self::toggle('slides', $r, 'active'),
                ],
                'fields' => [
                    ['name' => 'title', 'label' => 'Judul besar', 'type' => 'text', 'rules' => 'required|string|max:120'],
                    ['name' => 'text', 'label' => 'Teks pendukung', 'type' => 'textarea', 'rows' => 3, 'rules' => 'nullable|string|max:300'],
                    ['name' => 'tab', 'label' => 'Label tab', 'type' => 'text', 'half' => true, 'rules' => 'nullable|string|max:30'],
                    ['name' => 'button', 'label' => 'Teks tombol', 'type' => 'text', 'half' => true, 'rules' => 'nullable|string|max:40'],
                    ['name' => 'link', 'label' => 'Tujuan tombol', 'type' => 'select', 'rules' => 'nullable|in:'.implode(',', array_keys(self::LINKS)), 'options' => fn () => self::LINKS],
                    ['name' => 'image', 'label' => 'Foto latar (opsional)', 'type' => 'image', 'rules' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', 'dir' => 'slides', 'hint' => 'Ukuran ideal 1920 × 700 px, JPG atau WebP, maksimal 2 MB (disarankan di bawah 400 KB). Jika kosong, memakai latar biru bawaan. Foto otomatis diberi lapisan gelap agar tulisan tetap terbaca.'],
                    ['name' => 'image_fit', 'label' => 'Cara menampilkan foto', 'type' => 'select', 'rules' => 'required|in:auto,cover,contain', 'default' => 'auto', 'options' => fn () => ['auto' => 'Otomatis (sesuai bentuk foto)', 'cover' => 'Penuh selebar banner (foto dipotong)', 'contain' => 'Utuh di sisi kanan (foto tidak dipotong)'], 'hint' => 'Otomatis: foto mendatar dibuat penuh, foto tegak atau persegi ditampilkan utuh di kanan dengan latar foto yang diburamkan.'],
                    ['name' => 'image_position', 'label' => 'Bagian foto yang diutamakan', 'type' => 'select', 'rules' => 'required|in:center,right,left,top,bottom', 'default' => 'center', 'options' => fn () => ['center' => 'Tengah', 'right' => 'Kanan', 'left' => 'Kiri', 'top' => 'Atas', 'bottom' => 'Bawah'], 'hint' => 'Bagian yang tetap terlihat saat foto dipotong, misalnya di layar HP.'],
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
                'model' => Doctor::class, 'one' => 'dokter', 'title' => 'Profil dokter', 'simrs' => ! $manual, 'noCreate' => ! $manual, 'noDelete' => ! $manual,
                'desc' => $manual
                    ? 'Kelola data dokter, poli, status, dan jadwal praktik langsung di sini (mode manual, selama SIMRS belum terhubung).'
                    : 'Nama, poli, jadwal, dan status dokter diambil dari SIMRS. Di sini Anda hanya mengatur tampilan dokter di situs.',
                'with' => ['poli'], 'order' => ['name', 'asc'], 'search' => ['name'],
                'filters' => [
                    'poli_id' => ['label' => 'Semua poliklinik', 'options' => fn () => Poli::orderBy('sort')->pluck('name', 'id')->all()],
                    'status' => ['label' => 'Semua status', 'options' => fn () => ['Aktif' => 'Aktif', 'Cuti' => 'Cuti', 'Nonaktif' => 'Nonaktif']],
                ],
                'columns' => [
                    'Dokter' => fn ($r) => '<div class="cellrow">'.($r->photo ? '<img class="av" src="'.e(asset('storage/'.$r->photo)).'" alt="">' : self::avatar($r->color, $r->initials())).'<div><span class="main">'.e($r->name).'</span><span class="sub">'.e($r->poli?->name ?? '—').'</span></div></div>',
                    'Hari praktik' => fn ($r) => e(collect($r->schedule ?? [])->map(fn ($s) => (int) $s[0])->unique()->sortBy(fn ($d) => ($d + 6) % 7)->map(fn ($d) => mb_substr(Doctor::DAYS[$d], 0, 3))->implode(', ') ?: '—'),
                    ($manual ? 'Status' : 'Status SIMRS') => fn ($r) => self::badge($r->status),
                    'Tampil' => fn ($r) => self::toggle('doctors', $r, 'show'),
                ],
                'fields' => $manual ? [
                    ['name' => 'name', 'label' => 'Nama lengkap dan gelar', 'type' => 'text', 'rules' => 'required|string|max:120', 'hint' => 'Contoh: dr. Maria Situmorang, Sp.A'],
                    ['name' => 'poli_id', 'label' => 'Poliklinik', 'type' => 'select', 'half' => true, 'rules' => 'required|exists:polis,id', 'options' => fn () => Poli::orderBy('sort')->pluck('name', 'id')->all()],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'half' => true, 'rules' => 'required|in:Aktif,Cuti,Nonaktif', 'default' => 'Aktif', 'options' => fn () => ['Aktif' => 'Aktif', 'Cuti' => 'Cuti', 'Nonaktif' => 'Nonaktif (tidak tampil)']],
                    ['name' => 'schedule', 'label' => 'Jadwal praktik', 'type' => 'schedule', 'rules' => 'nullable|array|max:21', 'hint' => 'Satu baris untuk satu sesi. Bila praktik dua kali sehari, misalnya pagi dan sore, tambahkan dua baris dengan hari yang sama.'],
                    ['name' => 'photo', 'label' => 'Foto dokter (opsional)', 'type' => 'image', 'rules' => 'nullable|image|max:2048', 'dir' => 'doctors'],
                    ['name' => 'color', 'label' => 'Warna avatar', 'type' => 'color', 'rules' => 'required|in:'.implode(',', self::COLORS)],
                    ['name' => 'bio', 'label' => 'Profil singkat', 'type' => 'textarea', 'rows' => 3, 'rules' => 'nullable|string|max:600'],
                    ['name' => 'simrs_code', 'label' => 'Kode dokter di SIMRS (opsional)', 'type' => 'text', 'hidePrefix' => 'MAN-', 'rules' => fn ($m) => 'nullable|string|max:40|unique:doctors,simrs_code,'.($m->id ?? 'NULL'), 'hint' => 'Isi nanti saat SIMRS akan dihubungkan, supaya foto dan profil dokter ini tetap terpakai setelah sinkron.'],
                    ['name' => 'show', 'label' => 'Tampilkan di situs', 'type' => 'toggle', 'desc' => 'Muncul di halaman Dokter, Jadwal, dan formulir janji temu.', 'default' => true],
                ] : [
                    ['name' => 'name', 'label' => 'Nama lengkap dan gelar', 'type' => 'readonly', 'value' => fn ($r) => e($r->name)],
                    ['name' => 'poli_id', 'label' => 'Poliklinik', 'type' => 'readonly', 'half' => true, 'value' => fn ($r) => e($r->poli?->name ?? '—')],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'readonly', 'half' => true, 'value' => fn ($r) => self::badge($r->status)],
                    ['name' => 'schedule', 'label' => 'Jadwal praktik', 'type' => 'readonly', 'value' => fn ($r) => e($r->scheduleText()) ?: 'Belum ada jadwal di SIMRS'],
                    ['name' => 'photo', 'label' => 'Foto dokter (opsional)', 'type' => 'image', 'rules' => 'nullable|image|max:2048', 'dir' => 'doctors'],
                    ['name' => 'color', 'label' => 'Warna avatar', 'type' => 'color', 'rules' => 'required|in:'.implode(',', self::COLORS)],
                    ['name' => 'bio', 'label' => 'Profil singkat', 'type' => 'textarea', 'rows' => 3, 'rules' => 'nullable|string|max:600'],
                    ['name' => 'show', 'label' => 'Tampilkan di situs', 'type' => 'toggle', 'desc' => 'Muncul di halaman Dokter, Jadwal, dan formulir janji temu.'],
                ],
                'before' => function ($model, array &$data, bool $isNew) use ($manual) {
                    if (! $manual) {
                        return;
                    }
                    $data['schedule'] = self::cleanSchedule($data['schedule'] ?? []);
                    $data['simrs_code'] = ($data['simrs_code'] ?? '') ?: ($model->exists && ! str_starts_with((string) $model->simrs_code, 'MAN-') ? $model->simrs_code : ($model->simrs_code ?: 'MAN-'.strtoupper(Str::random(8))));
                },
                'canDelete' => fn ($r) => $r->appointments()->exists() ? 'Dokter ini sudah memiliki janji temu. Ubah statusnya menjadi Nonaktif agar tidak tampil lagi.' : null,
                'label' => fn ($r) => $r->name,
            ],

            'clinics' => [
                'model' => Poli::class, 'one' => 'poliklinik', 'title' => 'Poliklinik', 'simrs' => ! $manual, 'noCreate' => ! $manual, 'noDelete' => ! $manual, 'sortable' => true,
                'desc' => $manual ? 'Kelola daftar poliklinik, deskripsi, urutan, dan tampilannya di situs (mode manual).' : 'Daftar poli diambil dari SIMRS. Atur deskripsi, urutan, dan tampilannya di situs.',
                'order' => ['sort', 'asc'], 'search' => ['name', 'description'], 'withCount' => ['doctors'],
                'columns' => [
                    'Poliklinik' => fn ($r) => '<span class="main">'.e($r->name).'</span><span class="sub">'.e(Str::limit($r->description, 90)).'</span>',
                    'Dokter' => fn ($r) => $r->doctors_count,
                    'Unggulan' => fn ($r) => $r->featured ? '<span class="badge b-blue">Unggulan</span>' : '—',
                    'Tampil' => fn ($r) => self::toggle('clinics', $r, 'active'),
                ],
                'fields' => array_values(array_filter([
                    $manual
                        ? ['name' => 'name', 'label' => 'Nama poliklinik', 'type' => 'text', 'rules' => fn ($m) => 'required|string|max:80|unique:polis,name,'.($m->id ?? 'NULL'), 'hint' => 'Contoh: Poli Anak, Poli Penyakit Dalam.']
                        : ['name' => 'name', 'label' => 'Nama poliklinik', 'type' => 'readonly', 'value' => fn ($r) => e($r->name)],
                    ['name' => 'description', 'label' => 'Deskripsi di situs', 'type' => 'textarea', 'rows' => 3, 'rules' => 'nullable|string|max:300'],
                    ['name' => 'active', 'label' => 'Tampilkan di situs', 'type' => 'toggle', 'desc' => 'Poli yang disembunyikan tidak muncul di situs dan formulir janji.', 'default' => true],
                    ['name' => 'featured', 'label' => 'Unggulan di beranda', 'type' => 'toggle', 'desc' => 'Tampil sebagai kotak biru di atas daftar poliklinik.'],
                    $manual ? ['name' => 'simrs_code', 'label' => 'Kode poli di SIMRS (opsional)', 'type' => 'text', 'hidePrefix' => 'MAN-', 'rules' => fn ($m) => 'nullable|string|max:40|unique:polis,simrs_code,'.($m->id ?? 'NULL'), 'hint' => 'Isi nanti saat SIMRS akan dihubungkan.'] : null,
                ])),
                'before' => function ($model, array &$data, bool $isNew) use ($manual) {
                    if (! $manual) {
                        return;
                    }
                    $data['simrs_code'] = ($data['simrs_code'] ?? '') ?: ($model->simrs_code ?: 'MAN-'.strtoupper(Str::random(8)));
                    if ($isNew) {
                        $data['sort'] = (Poli::max('sort') ?? 0) + 1;
                    }
                },
                'after' => function ($model) {
                    if ($model->featured) {
                        Poli::where('id', '!=', $model->id)->update(['featured' => false]);
                    }
                },
                'canDelete' => fn ($r) => $r->doctors()->exists() ? 'Poliklinik ini masih memiliki dokter. Pindahkan dokternya ke poli lain atau matikan sakelar Tampil.' : null,
                'label' => fn ($r) => $r->name,
            ],

            'leaves' => [
                'model' => \App\Models\DoctorLeave::class, 'one' => 'cuti dokter', 'title' => 'Cuti dokter', 'simrs' => ! $manual,
                'noCreate' => ! $manual, 'noDelete' => ! $manual, 'noEdit' => ! $manual,
                'desc' => $manual ? 'Catat cuti atau libur dokter. Selama cuti, dokter tidak bisa dipilih di formulir janji temu dan jadwalnya bertanda Cuti.' : 'Cuti dokter diambil dari SIMRS.',
                'with' => ['doctor.poli'], 'order' => ['date_from', 'desc'],
                'filters' => ['doctor_id' => ['label' => 'Semua dokter', 'options' => fn () => Doctor::orderBy('name')->pluck('name', 'id')->all()]],
                'columns' => [
                    'Dokter' => fn ($r) => '<span class="main">'.e($r->doctor?->name ?? '—').'</span><span class="sub">'.e($r->doctor?->poli?->name ?? '').'</span>',
                    'Periode' => fn ($r) => $r->date_from->translatedFormat('d M Y').($r->date_to->ne($r->date_from) ? ' – '.$r->date_to->translatedFormat('d M Y') : ''),
                    'Keterangan' => fn ($r) => e($r->reason ?: '—'),
                    'Status' => fn ($r) => $r->date_to->lt(today()) ? '<span class="badge b-grey">Selesai</span>' : ($r->date_from->gt(today()) ? '<span class="badge b-blue">Akan datang</span>' : '<span class="badge b-amber">Berlangsung</span>'),
                ],
                'fields' => [
                    ['name' => 'doctor_id', 'label' => 'Dokter', 'type' => 'select', 'rules' => 'required|exists:doctors,id', 'options' => fn () => Doctor::orderBy('name')->pluck('name', 'id')->all()],
                    ['name' => 'date_from', 'label' => 'Mulai tanggal', 'type' => 'date', 'half' => true, 'rules' => 'required|date'],
                    ['name' => 'date_to', 'label' => 'Sampai tanggal', 'type' => 'date', 'half' => true, 'rules' => 'required|date|after_or_equal:date_from'],
                    ['name' => 'reason', 'label' => 'Keterangan', 'type' => 'text', 'rules' => 'nullable|string|max:150', 'hint' => 'Contoh: Cuti tahunan, seminar, atau libur nasional.'],
                ],
                'before' => function ($model, array &$data, bool $isNew) {
                    if ($isNew) {
                        $data['simrs_code'] = 'MAN-'.strtoupper(Str::random(10));
                    }
                },
                'label' => fn ($r) => ($r->doctor?->name ?? 'Dokter').' '.$r->date_from->format('d/m/Y'),
            ],

            'appointments' => [
                'model' => Appointment::class, 'one' => 'janji temu', 'title' => 'Janji temu', 'simrs' => ! $manual, 'noCreate' => true, 'noDelete' => true, 'noEdit' => ! $manual,
                'desc' => $manual
                    ? 'Janji temu dari situs. Dalam mode manual, ubah status janji di sini: Dikonfirmasi, Hadir, Selesai, atau Batal.'
                    : 'Pantauan janji temu yang dibuat di situs dan diteruskan ke SIMRS. Konfirmasi, kehadiran, dan pembatalan dilakukan di SIMRS.',
                'with' => ['doctor.poli'], 'order' => ['date', 'desc'], 'search' => ['code', 'name', 'phone', 'simrs_booking_code'],
                'filters' => [
                    'status' => ['label' => 'Semua status', 'options' => fn () => array_combine(Appointment::STATUSES, Appointment::STATUSES)],
                    'date' => ['label' => 'Tanggal', 'type' => 'date'],
                ],
                'columns' => array_filter([
                    'Kode' => fn ($r) => '<span class="main">'.e($r->code).'</span><span class="sub">'.e($r->simrs_booking_code ?: '').'</span>',
                    'Pasien' => fn ($r) => '<span class="main">'.e($r->name).'</span><span class="sub">'.e($r->phone).($r->medical_record ? ' · '.e($r->medical_record) : '').'</span>',
                    'Dokter' => fn ($r) => e($r->doctor?->name ?? '—').'<span class="sub">'.e($r->doctor?->poli?->name ?? '').'</span>',
                    'Waktu' => fn ($r) => $r->date->translatedFormat('D, d M Y').'<span class="sub">Pukul '.e($r->time).($r->queue_number ? ' · Antrean '.e($r->queue_number) : '').'</span>',
                    'Bayar' => fn ($r) => e($r->payment),
                    ($manual ? 'Status' : 'Status SIMRS') => fn ($r) => self::badge($r->status),
                    'Pengiriman' => $manual ? null : fn ($r) => $r->simrs_sent_at ? '<span class="badge b-green">Terkirim</span>' : '<span class="badge b-red" title="'.e($r->simrs_error).'">Gagal</span>'
                        .(! $r->simrs_sent_at ? '<form method="post" class="inline" action="'.route('admin.appointments.resend', $r).'">'.csrf_field().'<button class="btn sm" style="margin-left:6px">Kirim ulang</button></form>' : ''),
                ]),
                'fields' => $manual ? [
                    ['name' => 'code', 'label' => 'Kode janji', 'type' => 'readonly', 'half' => true, 'lock' => false, 'value' => fn ($r) => e($r->code).($r->queue_number ? ' · Antrean '.e($r->queue_number) : '')],
                    ['name' => 'doctor_id', 'label' => 'Dokter', 'type' => 'readonly', 'half' => true, 'lock' => false, 'value' => fn ($r) => e(($r->doctor?->name ?? '—').' · '.($r->doctor?->poli?->name ?? ''))],
                    ['name' => 'name', 'label' => 'Pasien', 'type' => 'readonly', 'lock' => false, 'value' => fn ($r) => e($r->name).' · <a href="tel:'.e($r->phone).'">'.e($r->phone).'</a>'.($r->medical_record ? ' · No. RM '.e($r->medical_record) : '').($r->nik ? '<br>NIK '.e($r->nik) : '')],
                    ['name' => 'date', 'label' => 'Waktu', 'type' => 'readonly', 'half' => true, 'lock' => false, 'value' => fn ($r) => $r->date->translatedFormat('l, d F Y').' · pukul '.e($r->time)],
                    ['name' => 'payment', 'label' => 'Pembayaran', 'type' => 'readonly', 'half' => true, 'lock' => false, 'value' => fn ($r) => e($r->payment).($r->bpjs_number ? '<br>BPJS '.e($r->bpjs_number).($r->referral_number ? ' · Rujukan '.e($r->referral_number) : '') : '')],
                    ['name' => 'note', 'label' => 'Keluhan dari pasien', 'type' => 'readonly', 'lock' => false, 'value' => fn ($r) => $r->note ? nl2br(e($r->note)) : '<span style="color:var(--muted)">Tidak diisi</span>'],
                    ['name' => 'status', 'label' => 'Status janji', 'type' => 'select', 'rules' => 'required|in:'.implode(',', Appointment::STATUSES), 'options' => fn () => array_combine(Appointment::STATUSES, Appointment::STATUSES)],
                ] : [],
                'label' => fn ($r) => $r->code,
            ],

            'jobs' => [
                'model' => JobOpening::class, 'one' => 'lowongan', 'title' => 'Lowongan kerja',
                'desc' => 'Lowongan yang tampil di halaman Karier. Pengunjung bisa melamar langsung dengan mengunggah CV.',
                'order' => ['id', 'desc'], 'search' => ['title', 'department'], 'withCount' => ['applications'],
                'filters' => [
                    'status' => ['label' => 'Semua status', 'options' => fn () => ['Dibuka' => 'Dibuka', 'Ditutup' => 'Ditutup', 'Draf' => 'Draf']],
                    'employment_type' => ['label' => 'Semua jenis', 'options' => fn () => array_combine(JobOpening::TYPES, JobOpening::TYPES)],
                ],
                'columns' => [
                    'Lowongan' => fn ($r) => '<span class="main">'.e($r->title).'</span><span class="sub">'.e(collect([$r->department, $r->employment_type, $r->education])->filter()->implode(' · ')).'</span>',
                    'Batas lamaran' => fn ($r) => $r->deadline ? $r->deadline->translatedFormat('d M Y').($r->deadline->lt(today()) ? '<span class="sub" style="color:var(--red)">Sudah lewat</span>' : '') : '<span class="sub">Tanpa batas</span>',
                    'Status' => fn ($r) => self::badge($r->status),
                    'Pelamar' => fn ($r) => '<a href="'.e(route('admin.crud.index', ['applications', 'job_opening_id' => $r->id])).'" style="font-weight:700">'.$r->applications_count.' pelamar</a>',
                ],
                'fields' => [
                    ['name' => 'title', 'label' => 'Posisi / judul lowongan', 'type' => 'text', 'rules' => 'required|string|max:150', 'hint' => 'Contoh: Perawat Pelaksana Rawat Inap.'],
                    ['name' => 'slug', 'label' => 'Slug alamat', 'type' => 'text', 'half' => true, 'rules' => 'nullable|alpha_dash|max:80', 'hint' => 'Kosongkan untuk dibuat otomatis dari judul.'],
                    ['name' => 'department', 'label' => 'Unit / bagian', 'type' => 'text', 'half' => true, 'rules' => 'nullable|string|max:80', 'hint' => 'Contoh: Keperawatan, Farmasi, Administrasi.'],
                    ['name' => 'employment_type', 'label' => 'Jenis pekerjaan', 'type' => 'select', 'half' => true, 'rules' => 'required|in:'.implode(',', JobOpening::TYPES), 'options' => fn () => array_combine(JobOpening::TYPES, JobOpening::TYPES), 'default' => 'Kontrak'],
                    ['name' => 'education', 'label' => 'Pendidikan minimal', 'type' => 'select', 'half' => true, 'rules' => 'nullable|in:'.implode(',', JobOpening::EDUCATION), 'options' => fn () => ['' => '— Tidak ditentukan —'] + array_combine(JobOpening::EDUCATION, JobOpening::EDUCATION)],
                    ['name' => 'location', 'label' => 'Lokasi kerja', 'type' => 'text', 'half' => true, 'rules' => 'required|string|max:80', 'default' => 'Sidikalang'],
                    ['name' => 'quota', 'label' => 'Jumlah dibutuhkan', 'type' => 'number', 'half' => true, 'rules' => 'nullable|integer|min:1|max:999'],
                    ['name' => 'deadline', 'label' => 'Batas akhir lamaran', 'type' => 'date', 'half' => true, 'rules' => 'nullable|date', 'hint' => 'Setelah tanggal ini, formulir lamaran otomatis ditutup.'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'half' => true, 'rules' => 'required|in:Dibuka,Ditutup,Draf', 'options' => fn () => ['Draf' => 'Draf (belum tampil)', 'Dibuka' => 'Dibuka (menerima lamaran)', 'Ditutup' => 'Ditutup (tampil, tidak menerima lamaran)'], 'default' => 'Draf'],
                    ['name' => 'summary', 'label' => 'Ringkasan singkat', 'type' => 'textarea', 'rows' => 2, 'rules' => 'nullable|string|max:300', 'hint' => 'Tampil di daftar lowongan. 1 sampai 2 kalimat.'],
                    ['name' => 'description', 'label' => 'Deskripsi pekerjaan', 'type' => 'textarea', 'rows' => 6, 'rules' => 'nullable|string|max:5000', 'hint' => 'Tugas dan tanggung jawab. Pisahkan paragraf dengan satu baris kosong.'],
                    ['name' => 'requirements', 'label' => 'Persyaratan', 'type' => 'textarea', 'rows' => 6, 'rules' => 'nullable|string|max:5000', 'hint' => 'Satu persyaratan per baris. Contoh: Memiliki STR aktif.'],
                    ['name' => 'benefits', 'label' => 'Yang kami tawarkan', 'type' => 'textarea', 'rows' => 4, 'rules' => 'nullable|string|max:3000', 'hint' => 'Opsional. Satu poin per baris, misalnya BPJS Ketenagakerjaan.'],
                ],
                'before' => function ($model, array &$data, bool $isNew) {
                    $base = Str::slug(($data['slug'] ?? '') ?: $data['title']);
                    $slug = $base;
                    $i = 2;
                    while (JobOpening::where('slug', $slug)->where('id', '!=', $model->id ?? 0)->exists()) {
                        $slug = $base.'-'.$i++;
                    }
                    $data['slug'] = $slug;
                    $data['education'] = ($data['education'] ?? '') ?: null;
                },
                'canDelete' => fn ($r) => $r->applications()->exists() ? 'Lowongan ini sudah memiliki pelamar. Ubah statusnya menjadi Ditutup, atau hapus pelamarnya lebih dulu.' : null,
                'label' => fn ($r) => $r->title,
            ],

            'applications' => [
                'model' => JobApplication::class, 'one' => 'pelamar', 'title' => 'Pelamar',
                'desc' => 'Lamaran yang masuk dari halaman Karier. Buka lamaran untuk melihat data lengkap, mengunduh CV, dan mengubah status.',
                'noCreate' => true, 'files' => ['cv_path', 'doc_path'],
                'with' => ['job'], 'order' => ['id', 'desc'], 'search' => ['name', 'email', 'phone', 'code'],
                'filters' => [
                    'job_opening_id' => ['label' => 'Semua lowongan', 'options' => fn () => JobOpening::orderByDesc('id')->pluck('title', 'id')->all()],
                    'status' => ['label' => 'Semua status', 'options' => fn () => array_combine(JobApplication::STATUSES, JobApplication::STATUSES)],
                ],
                'columns' => [
                    'Kode' => fn ($r) => '<span class="main">'.e($r->code).'</span><span class="sub">'.$r->created_at->translatedFormat('d M Y H.i').'</span>',
                    'Pelamar' => fn ($r) => '<span class="main">'.e($r->name).'</span><span class="sub">'.e($r->email).' · '.e($r->phone).'</span>',
                    'Lowongan' => fn ($r) => e($r->job?->title ?? '—'),
                    'Pendidikan' => fn ($r) => e($r->education).($r->major ? '<span class="sub">'.e($r->major).'</span>' : ''),
                    'Status' => fn ($r) => self::badge($r->status),
                    'Berkas' => fn ($r) => '<a href="'.e(route('admin.applications.file', [$r, 'cv'])).'" target="_blank" style="font-weight:700">CV</a>'.($r->doc_path ? ' · <a href="'.e(route('admin.applications.file', [$r, 'doc'])).'" target="_blank">Dokumen</a>' : ''),
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama pelamar', 'type' => 'readonly', 'half' => true, 'lock' => false, 'value' => fn ($r) => e($r->name)],
                    ['name' => 'job_opening_id', 'label' => 'Melamar untuk', 'type' => 'readonly', 'half' => true, 'lock' => false, 'value' => fn ($r) => e($r->job?->title ?? '—')],
                    ['name' => 'email', 'label' => 'Kontak', 'type' => 'readonly', 'lock' => false, 'value' => fn ($r) => '<a href="mailto:'.e($r->email).'">'.e($r->email).'</a> · <a href="tel:'.e($r->phone).'">'.e($r->phone).'</a> · <a href="https://wa.me/62'.e(substr($r->phone, 1)).'" target="_blank" rel="noopener">WhatsApp</a>'],
                    ['name' => 'birth_date', 'label' => 'Tanggal lahir', 'type' => 'readonly', 'half' => true, 'lock' => false, 'value' => fn ($r) => $r->birth_date ? $r->birth_date->translatedFormat('d F Y').' ('.$r->birth_date->age.' tahun)' : '—'],
                    ['name' => 'city', 'label' => 'Kota domisili', 'type' => 'readonly', 'half' => true, 'lock' => false, 'value' => fn ($r) => e($r->city ?: '—')],
                    ['name' => 'education', 'label' => 'Pendidikan', 'type' => 'readonly', 'half' => true, 'lock' => false, 'value' => fn ($r) => e(collect([$r->education, $r->major, $r->institution])->filter()->implode(' · '))],
                    ['name' => 'experience_years', 'label' => 'Pengalaman kerja', 'type' => 'readonly', 'half' => true, 'lock' => false, 'value' => fn ($r) => $r->experience_years ? $r->experience_years.' tahun' : 'Belum berpengalaman'],
                    ['name' => 'cover_letter', 'label' => 'Surat lamaran singkat', 'type' => 'readonly', 'lock' => false, 'value' => fn ($r) => $r->cover_letter ? nl2br(e($r->cover_letter)) : '<span style="color:var(--muted)">Tidak diisi</span>'],
                    ['name' => 'cv_path', 'label' => 'Berkas', 'type' => 'readonly', 'lock' => false, 'value' => fn ($r) => '<a class="btn sm" href="'.e(route('admin.applications.file', [$r, 'cv'])).'" target="_blank">Buka CV (PDF)</a>'.($r->doc_path ? ' <a class="btn sm" href="'.e(route('admin.applications.file', [$r, 'doc'])).'" target="_blank">Buka dokumen pendukung</a>' : '')],
                    ['name' => 'status', 'label' => 'Status lamaran', 'type' => 'select', 'half' => true, 'rules' => 'required|in:'.implode(',', JobApplication::STATUSES), 'options' => fn () => array_combine(JobApplication::STATUSES, JobApplication::STATUSES)],
                    ['name' => 'note', 'label' => 'Catatan internal', 'type' => 'textarea', 'rows' => 4, 'rules' => 'nullable|string|max:2000', 'hint' => 'Hanya terlihat oleh petugas. Misalnya hasil wawancara atau jadwal tes.'],
                ],
                'label' => fn ($r) => $r->code.' '.$r->name,
            ],

            'gallery' => [
                'model' => GalleryItem::class, 'one' => 'item galeri', 'title' => 'Galeri', 'sortable' => true,
                'desc' => 'Foto dan video kegiatan, fasilitas, dan suasana rumah sakit yang tampil di halaman Galeri.',
                'order' => ['sort', 'asc'], 'search' => ['title', 'album', 'caption'],
                'filters' => [
                    'type' => ['label' => 'Foto dan video', 'options' => fn () => ['photo' => 'Foto', 'video' => 'Video']],
                    'album' => ['label' => 'Semua album', 'options' => fn () => GalleryItem::whereNotNull('album')->distinct()->orderBy('album')->pluck('album', 'album')->all()],
                ],
                'columns' => [
                    'Item' => fn ($r) => '<div class="cellrow"><div class="mini-thumb">'.($r->thumbUrl() ? '<img src="'.e($r->thumbUrl()).'" alt="">' : '').'</div><div><span class="main">'.e($r->title).'</span><span class="sub">'.($r->type === 'video' ? 'Video' : 'Foto').($r->album ? ' · '.e($r->album) : '').'</span></div></div>',
                    'Tanggal' => fn ($r) => $r->taken_at ? $r->taken_at->translatedFormat('d M Y') : '—',
                    'Tampil' => fn ($r) => self::toggle('gallery', $r, 'show'),
                ],
                'fields' => [
                    ['name' => 'type', 'label' => 'Jenis', 'type' => 'select', 'half' => true, 'rules' => 'required|in:photo,video', 'options' => fn () => ['photo' => 'Foto', 'video' => 'Video YouTube']],
                    ['name' => 'album', 'label' => 'Album', 'type' => 'text', 'half' => true, 'rules' => 'nullable|string|max:60', 'hint' => 'Pengelompokan, misalnya Fasilitas, Kegiatan, atau HUT RS. Dipakai sebagai penyaring di halaman Galeri.'],
                    ['name' => 'title', 'label' => 'Judul', 'type' => 'text', 'rules' => 'required|string|max:120'],
                    ['name' => 'image', 'label' => 'Foto', 'type' => 'image', 'dir' => 'gallery', 'rules' => fn ($m) => ($m->image ? 'nullable' : 'required_if:type,photo').'|nullable|image|mimes:jpg,jpeg,png,webp|max:3072', 'hint' => 'Wajib untuk jenis Foto. Ukuran ideal 1600 × 1067 px, maksimal 3 MB. Untuk video, foto ini opsional sebagai gambar sampul.'],
                    ['name' => 'video_url', 'label' => 'Tautan video YouTube', 'type' => 'text', 'rules' => 'nullable|required_if:type,video|url|max:255', 'hint' => 'Wajib untuk jenis Video. Contoh: https://www.youtube.com/watch?v=xxxx atau https://youtu.be/xxxx'],
                    ['name' => 'caption', 'label' => 'Keterangan', 'type' => 'textarea', 'rows' => 2, 'rules' => 'nullable|string|max:400'],
                    ['name' => 'taken_at', 'label' => 'Tanggal', 'type' => 'date', 'half' => true, 'rules' => 'nullable|date'],
                    ['name' => 'show', 'label' => 'Tampilkan di situs', 'type' => 'toggle', 'default' => true],
                ],
                'before' => function ($model, array &$data, bool $isNew) {
                    if (($data['type'] ?? '') === 'video' && ! GalleryItem::youtubeId($data['video_url'] ?? '')) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['video_url' => 'Tautan YouTube tidak dikenali. Salin tautan dari tombol Bagikan di YouTube.']);
                    }
                    if (($data['type'] ?? '') === 'photo') {
                        $data['video_url'] = null;
                    }
                },
                'label' => fn ($r) => $r->title,
            ],

            'guides' => [
                'model' => Guide::class, 'one' => 'panduan', 'title' => 'Panduan pasien', 'sortable' => true,
                'desc' => 'Panduan untuk pasien dan keluarga, misalnya alur pendaftaran, BPJS, rawat inap, dan tata tertib besuk.',
                'order' => ['sort', 'asc'], 'search' => ['title', 'content'],
                'filters' => ['category' => ['label' => 'Semua kategori', 'options' => fn () => array_combine(Guide::CATEGORIES, Guide::CATEGORIES)]],
                'columns' => [
                    'Panduan' => fn ($r) => '<span class="main">'.e($r->title).'</span><span class="sub">'.e(Str::limit(preg_replace('/\s+/', ' ', (string) $r->content), 90)).'</span>',
                    'Kategori' => fn ($r) => e($r->category),
                    'Tampil' => fn ($r) => self::toggle('guides', $r, 'show'),
                ],
                'fields' => [
                    ['name' => 'title', 'label' => 'Judul panduan', 'type' => 'text', 'rules' => 'required|string|max:120'],
                    ['name' => 'category', 'label' => 'Kategori', 'type' => 'select', 'half' => true, 'rules' => 'required|in:'.implode(',', Guide::CATEGORIES), 'options' => fn () => array_combine(Guide::CATEGORIES, Guide::CATEGORIES)],
                    ['name' => 'content', 'label' => 'Isi panduan', 'type' => 'textarea', 'rows' => 10, 'rules' => 'required|string|max:8000', 'hint' => 'Pisahkan paragraf dengan satu baris kosong. Untuk daftar poin, awali setiap baris dengan tanda minus (-).'],
                    ['name' => 'show', 'label' => 'Tampilkan di situs', 'type' => 'toggle', 'default' => true],
                ],
                'label' => fn ($r) => $r->title,
            ],

            'faqs' => [
                'model' => Faq::class, 'one' => 'pertanyaan', 'title' => 'FAQ', 'sortable' => true,
                'desc' => 'Pertanyaan yang sering diajukan beserta jawabannya. Tampil di halaman Panduan Pasien dan bisa dicari pengunjung.',
                'order' => ['sort', 'asc'], 'search' => ['question', 'answer'],
                'filters' => ['category' => ['label' => 'Semua kategori', 'options' => fn () => Faq::distinct()->orderBy('category')->pluck('category', 'category')->all()]],
                'columns' => [
                    'Pertanyaan' => fn ($r) => '<span class="main">'.e($r->question).'</span><span class="sub">'.e(Str::limit($r->answer, 100)).'</span>',
                    'Kategori' => fn ($r) => e($r->category),
                    'Tampil' => fn ($r) => self::toggle('faqs', $r, 'show'),
                ],
                'fields' => [
                    ['name' => 'question', 'label' => 'Pertanyaan', 'type' => 'text', 'rules' => 'required|string|max:200'],
                    ['name' => 'answer', 'label' => 'Jawaban', 'type' => 'textarea', 'rows' => 5, 'rules' => 'required|string|max:2000'],
                    ['name' => 'category', 'label' => 'Kategori', 'type' => 'text', 'half' => true, 'rules' => 'required|string|max:40', 'default' => 'Umum', 'hint' => 'Misalnya Umum, Pendaftaran, BPJS, Rawat inap, IGD.'],
                    ['name' => 'show', 'label' => 'Tampilkan di situs', 'type' => 'toggle', 'default' => true],
                ],
                'label' => fn ($r) => Str::limit($r->question, 60),
            ],

            'feedback' => [
                'model' => Feedback::class, 'one' => 'pengaduan', 'title' => 'Pengaduan & saran', 'noCreate' => true, 'files' => ['attachment'],
                'desc' => 'Pengaduan, saran, pujian, dan pertanyaan dari halaman Pengaduan & Saran. Tanggapan yang Anda tulis bisa dilihat pelapor lewat nomor tiketnya.',
                'order' => ['id', 'desc'], 'search' => ['code', 'name', 'phone', 'message', 'unit'],
                'filters' => [
                    'status' => ['label' => 'Semua status', 'options' => fn () => array_combine(Feedback::STATUSES, Feedback::STATUSES)],
                    'type' => ['label' => 'Semua jenis', 'options' => fn () => array_combine(Feedback::TYPES, Feedback::TYPES)],
                    'category' => ['label' => 'Semua kategori', 'options' => fn () => array_combine(Feedback::CATEGORIES, Feedback::CATEGORIES)],
                ],
                'columns' => [
                    'Tiket' => fn ($r) => '<span class="main">'.e($r->code).'</span><span class="sub">'.$r->created_at->translatedFormat('d M Y H.i').'</span>',
                    'Pelapor' => fn ($r) => '<span class="main">'.e($r->name).'</span><span class="sub">'.e($r->phone).'</span>',
                    'Jenis' => fn ($r) => e($r->type).'<span class="sub">'.e($r->category).($r->unit ? ' · '.e($r->unit) : '').'</span>',
                    'Isi' => fn ($r) => e(Str::limit($r->message, 80)),
                    'Status' => fn ($r) => self::badge($r->status),
                ],
                'fields' => [
                    ['name' => 'code', 'label' => 'Tiket', 'type' => 'readonly', 'half' => true, 'lock' => false, 'value' => fn ($r) => e($r->code).' · '.$r->created_at->translatedFormat('d M Y H.i')],
                    ['name' => 'type', 'label' => 'Jenis dan kategori', 'type' => 'readonly', 'half' => true, 'lock' => false, 'value' => fn ($r) => e($r->type.' · '.$r->category).($r->unit ? '<br>Unit: '.e($r->unit) : '')],
                    ['name' => 'name', 'label' => 'Pelapor', 'type' => 'readonly', 'lock' => false, 'value' => fn ($r) => e($r->name).' · <a href="tel:'.e($r->phone).'">'.e($r->phone).'</a> · <a href="https://wa.me/62'.e(substr($r->phone, 1)).'" target="_blank" rel="noopener">WhatsApp</a>'.($r->email ? ' · <a href="mailto:'.e($r->email).'">'.e($r->email).'</a>' : '')],
                    ['name' => 'message', 'label' => 'Isi laporan', 'type' => 'readonly', 'lock' => false, 'value' => fn ($r) => ($r->incident_date ? '<b>Tanggal kejadian:</b> '.$r->incident_date->translatedFormat('d F Y').'<br>' : '').nl2br(e($r->message))],
                    ['name' => 'attachment', 'label' => 'Lampiran', 'type' => 'readonly', 'lock' => false, 'value' => fn ($r) => $r->attachment ? '<a class="btn sm" href="'.e(route('admin.feedback.file', $r)).'" target="_blank">Buka lampiran</a>' : '<span style="color:var(--muted)">Tidak ada</span>'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'half' => true, 'rules' => 'required|in:'.implode(',', Feedback::STATUSES), 'options' => fn () => array_combine(Feedback::STATUSES, Feedback::STATUSES)],
                    ['name' => 'response', 'label' => 'Tanggapan untuk pelapor', 'type' => 'textarea', 'rows' => 5, 'rules' => 'nullable|string|max:3000', 'hint' => 'Terlihat oleh pelapor saat mengecek tiket di halaman Pengaduan & Saran. Tulis dengan sopan dan jelas.'],
                    ['name' => 'note', 'label' => 'Catatan internal', 'type' => 'textarea', 'rows' => 3, 'rules' => 'nullable|string|max:2000', 'hint' => 'Hanya terlihat oleh petugas, misalnya unit yang menindaklanjuti.'],
                ],
                'before' => function ($model, array &$data, bool $isNew) {
                    if (trim((string) ($data['response'] ?? '')) !== trim((string) $model->response)) {
                        $data['responded_at'] = filled($data['response'] ?? null) ? now() : null;
                    }
                },
                'label' => fn ($r) => $r->code,
            ],

            'users' => [
                'model' => User::class, 'one' => 'pengguna', 'title' => 'Pengguna',
                'scope' => fn ($q) => $q->where(fn ($w) => $w->whereNull('role_id')->orWhereNotIn('role_id', Role::executiveIds())),
                'desc' => 'Akun petugas yang dapat masuk ke backoffice. Hak akses diatur per peran.',
                'with' => ['role'], 'order' => ['name', 'asc'], 'search' => ['name', 'email'],
                'filters' => ['role_id' => ['label' => 'Semua peran', 'options' => fn () => Role::orderBy('name')->get()->reject->isExecutive()->pluck('name', 'id')->all()]],
                'columns' => [
                    'Pengguna' => fn ($r) => '<div class="cellrow">'.self::avatar(self::COLORS[crc32($r->email) % count(self::COLORS)], $r->initials()).'<div><span class="main">'.e($r->name).'</span><span class="sub">'.e($r->email).'</span></div></div>',
                    'Peran' => fn ($r) => e($r->role?->name ?? '—'),
                    'Terakhir masuk' => fn ($r) => $r->last_login_at?->translatedFormat('d M Y H.i') ?? 'Belum pernah',
                    'Aktif' => fn ($r) => $r->id === auth()->id() ? self::badge('Aktif') : self::toggle('users', $r, 'is_active'),
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'rules' => 'required|string|max:100'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'rules' => fn ($m) => 'required|email|max:150|unique:users,email,'.($m->id ?? 'NULL')],
                    ['name' => 'role_id', 'label' => 'Peran', 'type' => 'select', 'half' => true, 'rules' => fn () => 'required|in:'.implode(',', Role::all()->reject->isExecutive()->pluck('id')->all()), 'options' => fn () => Role::orderBy('name')->get()->reject->isExecutive()->pluck('name', 'id')->all()],
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
