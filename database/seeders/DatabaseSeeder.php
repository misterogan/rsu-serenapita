<?php

namespace Database\Seeders;

use App\Admin\Modules;
use App\Models\Appointment;
use App\Models\ChatConversation;
use App\Models\Doctor;
use App\Models\Menu;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Phone;
use App\Models\Poli;
use App\Models\Role;
use App\Models\Service;
use App\Models\Slide;
use App\Models\User;
use App\Services\Simrs\SimrsSync;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /* Peran & pengguna */
        $admin = Role::create(['name' => Role::ADMIN, 'permissions' => array_keys(Modules::all())]);
        $humas = Role::create(['name' => 'Humas', 'permissions' => ['dashboard', 'livechat', 'news', 'categories', 'slides', 'info', 'services', 'menu', 'chatbot', 'profile']]);
        $daftar = Role::create(['name' => 'Pendaftaran', 'permissions' => ['dashboard', 'livechat', 'appointments', 'appointment-form', 'schedule']]);
        Role::create(['name' => 'Pelayanan Medis', 'permissions' => ['dashboard', 'doctors', 'clinics', 'schedule', 'leaves', 'appointments']]);

        User::create(['name' => 'Admin Sistem', 'email' => 'admin@rsuserenapita.co.id', 'password' => 'serenapita123', 'role_id' => $admin->id, 'is_active' => true]);
        User::create(['name' => 'Rina Simamora', 'email' => 'humas@rsuserenapita.co.id', 'password' => 'serenapita123', 'role_id' => $humas->id, 'is_active' => true]);
        User::create(['name' => 'Ferdinan Ujung', 'email' => 'pendaftaran@rsuserenapita.co.id', 'password' => 'serenapita123', 'role_id' => $daftar->id, 'is_active' => true]);

        /* Menu situs */
        $n = 0;
        foreach (['home' => 'Beranda', 'news' => 'Berita', 'doctors' => 'Dokter', 'schedule' => 'Jadwal', 'services' => 'Layanan', 'appointment' => 'Buat Janji', 'contact' => 'Kontak'] as $key => $label) {
            Menu::create(['key' => $key, 'label' => $label, 'route' => $key, 'visible' => true, 'locked' => $key === 'home', 'sort' => ++$n]);
        }

        /* Nomor telepon (ganti dengan nomor asli di menu Profil & kontak) */
        Phone::create(['label' => 'IGD 24 jam', 'number' => '(0627) 000-000', 'kind' => 'tel', 'show_top' => true, 'show_igd' => true, 'show_contact' => true, 'show_footer' => true, 'sort' => 0]);
        Phone::create(['label' => 'Pendaftaran', 'number' => '(0627) 000-001', 'kind' => 'tel', 'show_contact' => true, 'show_footer' => true, 'sort' => 1]);
        Phone::create(['label' => 'WhatsApp informasi', 'number' => '0812-0000-0000', 'kind' => 'wa', 'show_contact' => true, 'sort' => 2]);

        /* Data SIMRS (driver fake) */
        app(SimrsSync::class)->run('awal');
        $desc = [
            'P01' => 'Diabetes, hipertensi, gangguan lambung, ginjal, dan penyakit dalam lainnya.',
            'P02' => 'Pemeriksaan tumbuh kembang, imunisasi, dan penyakit pada bayi serta anak.',
            'P03' => 'Pemeriksaan kehamilan, USG, persalinan, dan kesehatan reproduksi wanita.',
            'P04' => 'Konsultasi tindakan bedah, perawatan luka, benjolan, hernia, dan usus buntu.',
            'P05' => 'Stroke, sakit kepala menahun, kejang, dan nyeri saraf.',
            'P06' => 'Pemeriksaan tajam penglihatan, katarak, dan infeksi mata.',
            'P07' => 'Gangguan telinga, hidung, tenggorokan, sinusitis, dan amandel.',
            'P08' => 'Tambal, cabut, pembersihan karang gigi, dan perawatan gusi.',
            'P09' => 'Pemeriksaan kesehatan umum, surat keterangan sehat, dan konsultasi awal.',
            'P10' => 'Rehabilitasi pasca stroke, cedera otot, dan nyeri sendi.',
        ];
        foreach ($desc as $code => $d) {
            Poli::where('simrs_code', $code)->update(['description' => $d, 'featured' => $code === 'P01']);
        }

        /* Berita */
        $cats = collect(['Kegiatan', 'Pengumuman', 'Tips Kesehatan'])->mapWithKeys(fn ($c) => [$c => NewsCategory::create(['name' => $c])->id]);
        $news = [
            ['Cek tekanan darah dan gula darah gratis di halaman rumah sakit', -6, 'Kegiatan', 'heart', 'Pos pemeriksaan gratis dibuka setiap Sabtu pagi selama bulan ini.', "Pos pemeriksaan dibuka pukul 07.00 sampai 10.00 di halaman depan rumah sakit. Warga cukup membawa KTP.\n\nBila hasilnya di atas batas normal, warga akan diarahkan untuk konsultasi ke Poli Penyakit Dalam."],
            ['Poli Anak kini membuka praktik sore setiap hari Kamis', -13, 'Pengumuman', 'child', 'Poli Anak menambah jadwal praktik pada Kamis pukul 14.00 sampai 16.00.', "Jadwal tambahan ini dilayani oleh dr. Maria Situmorang, Sp.A. Pasien dapat mendaftar melalui menu Buat Janji.\n\nLayanan imunisasi dasar juga tersedia pada jadwal sore. Bawa buku KIA anak saat datang."],
            ['Waspada demam berdarah saat musim hujan di Dairi', -21, 'Tips Kesehatan', 'drop', 'Kenali tanda bahaya demam berdarah dan lakukan 3M Plus di rumah.', "Demam tinggi mendadak selama 2 sampai 7 hari, nyeri di belakang mata, dan bintik merah di kulit perlu diwaspadai.\n\nSegera bawa pasien ke IGD bila muncul muntah terus-menerus, nyeri perut hebat, atau perdarahan gusi dan hidung."],
            ['Pendaftaran poli kini bisa dilakukan secara online', -31, 'Pengumuman', 'phone', 'Pasien dapat memilih dokter, tanggal, dan jam kedatangan melalui situs resmi.', "Setelah mendaftar, pasien mendapat kode janji temu dan nomor antrean dari sistem rumah sakit.\n\nPasien BPJS tetap wajib membawa kartu BPJS Kesehatan dan surat rujukan yang masih berlaku."],
            ['Senam sehat lansia rutin setiap Jumat pagi', -42, 'Kegiatan', 'sun', 'Tim fisioterapi mengajak warga lanjut usia mengikuti senam ringan.', 'Senam dimulai pukul 06.30 di halaman rumah sakit dan dipandu oleh fisioterapis.'],
            ['Penikmat kopi Sidikalang, ini batas aman bagi penderita hipertensi', -54, 'Tips Kesehatan', 'cup', 'Penderita darah tinggi tetap bisa menikmati kopi dengan beberapa catatan.', "Kafein dapat menaikkan tekanan darah sementara.\n\nKonsultasikan dengan dokter penyakit dalam untuk batas yang sesuai dengan kondisi Anda."],
        ];
        foreach ($news as $i => $n) {
            News::create(['title' => $n[0], 'slug' => Str::slug($n[0]), 'published_at' => today()->addDays($n[1]), 'category_id' => $cats[$n[2]], 'illustration' => $n[3],
                'excerpt' => $n[4], 'body' => $n[5], 'status' => 'Terbit', 'featured' => $i < 4, 'author_id' => 1]);
        }

        /* Slider */
        foreach ([
            ['Sehat bersama, dekat dengan keluarga Dairi', 'Pelayanan rawat jalan, rawat inap, dan gawat darurat dalam satu tempat di pusat Kota Sidikalang.', 'Pelayanan', 'Buat janji temu', 'appointment'],
            ['Daftar poli tanpa antre panjang', 'Pilih dokter, tanggal, dan jam praktik dari rumah.', 'Pendaftaran', 'Lihat jadwal dokter', 'schedule'],
            ['Melayani pasien BPJS dan umum', 'Bawa kartu BPJS Kesehatan dan surat rujukan dari faskes tingkat pertama.', 'BPJS', 'Kenali layanan kami', 'services'],
        ] as $i => $s) {
            Slide::create(['title' => $s[0], 'text' => $s[1], 'tab' => $s[2], 'button' => $s[3], 'link' => $s[4], 'active' => true, 'sort' => $i + 1]);
        }

        /* Layanan */
        foreach ([
            ['IGD 24 jam', 'Penanganan gawat darurat setiap saat dengan dokter jaga dan ambulans.', 'cross'],
            ['Rawat inap', 'Kamar kelas I, II, III, dan VIP untuk pasien BPJS maupun umum.', 'bed'],
            ['Laboratorium', 'Pemeriksaan darah, urine, dan kimia klinik dengan hasil cepat.', 'lab'],
            ['Radiologi', 'Rontgen dan USG untuk membantu dokter menegakkan diagnosis.', 'scan'],
            ['Farmasi', 'Apotek rumah sakit melayani resep rawat jalan dan rawat inap.', 'pill'],
            ['Ruang bersalin', 'Pendampingan persalinan normal dan tindakan oleh dokter kandungan.', 'heart'],
            ['Kamar operasi', 'Tindakan bedah terencana maupun darurat.', 'knife'],
            ['Ambulans', 'Layanan antar jemput pasien dan rujukan ke rumah sakit lain.', 'amb'],
        ] as $i => $s) {
            Service::create(['name' => $s[0], 'description' => $s[1], 'icon' => $s[2], 'show' => true, 'sort' => $i + 1]);
        }

        /* Contoh janji temu & live chat */
        $names = ['Rosmawati Sitorus', 'Hendra Manurung', 'Sari Br. Ginting', 'Jonatan Sembiring', 'Lina Purba', 'Andreas Tarigan', 'Melva Simbolon', 'Togar Hutagalung'];
        $doctors = Doctor::with('leaves')->get();
        foreach ($names as $i => $name) {
            $doc = $doctors[$i % $doctors->count()];
            $date = today()->addDays($i % 5);
            for ($k = 0; $k < 7 && ! $doc->slotFor($date->dayOfWeek); $k++) {
                $date->addDay();
            }
            $slot = $doc->slotFor($date->dayOfWeek);
            Appointment::create([
                'code' => 'SRN-'.strtoupper(Str::random(6)), 'doctor_id' => $doc->id, 'date' => $date, 'time' => $slot[1] ?? '08.00',
                'name' => $name, 'phone' => '0812'.str_pad((string) (3123456 + $i * 7919), 8, '0', STR_PAD_LEFT), 'birth_date' => today()->subYears(25 + $i * 3),
                'payment' => $i % 3 ? 'BPJS Kesehatan' : 'Umum', 'status' => $i % 3 ? 'Menunggu' : 'Dikonfirmasi',
                'simrs_booking_code' => 'SIM-'.strtoupper(Str::random(6)), 'queue_number' => (string) ($i + 3), 'simrs_sent_at' => now(),
            ]);
        }
        $c = ChatConversation::create(['token' => Str::random(48), 'name' => 'Rosmawati Sitorus', 'phone' => '0812-0312-3456', 'phone_norm' => '081203123456',
            'topic' => 'Jadwal dokter', 'status' => 'menunggu', 'sessions' => 1, 'started_at' => now()]);
        $c->addMessage('system', 'Chat dimulai · Jadwal dokter');
        $c->addMessage('visitor', 'Selamat pagi, apakah dr. Maria praktik hari Sabtu?');
    }
}
