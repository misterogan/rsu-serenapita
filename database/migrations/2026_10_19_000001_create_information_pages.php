<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_items', function (Blueprint $t) {
            $t->id();
            $t->string('type', 10)->default('photo'); // photo | video
            $t->string('title');
            $t->string('album')->nullable();
            $t->string('image')->nullable();
            $t->string('video_url')->nullable();
            $t->text('caption')->nullable();
            $t->date('taken_at')->nullable();
            $t->boolean('show')->default(true);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('guides', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('category')->default('Umum');
            $t->longText('content')->nullable();
            $t->boolean('show')->default(true);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('faqs', function (Blueprint $t) {
            $t->id();
            $t->string('question');
            $t->text('answer');
            $t->string('category')->default('Umum');
            $t->boolean('show')->default(true);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('feedback', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('type');      // Pengaduan | Saran | Pujian | Pertanyaan
            $t->string('category');
            $t->string('unit')->nullable();
            $t->string('name');
            $t->string('phone', 20)->index();
            $t->string('email')->nullable();
            $t->date('incident_date')->nullable();
            $t->text('message');
            $t->string('attachment')->nullable();
            $t->string('status')->default('Baru'); // Baru | Diproses | Selesai | Ditolak
            $t->text('response')->nullable();
            $t->timestamp('responded_at')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
        });

        // Menu "Tentang Kami" di header situs
        if (! DB::table('menus')->where('route', 'about')->exists()) {
            DB::table('menus')->insert(['key' => 'about', 'label' => 'Tentang Kami', 'route' => 'about', 'visible' => true, 'locked' => false,
                'sort' => (int) DB::table('menus')->max('sort') + 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Isi awal panduan pasien dan FAQ (bisa diubah di backoffice)
        $now = now();
        $guides = [
            ['Pendaftaran rawat jalan', 'Rawat jalan', "Daftar online lewat menu Buat Janji, atau langsung di loket pendaftaran.\n\n- Bawa KTP atau kartu identitas lain.\n- Bawa kartu berobat bila sudah pernah berobat.\n- Datang 15 menit sebelum jam janji temu.\n- Tunjukkan kode janji temu di loket."],
            ['Pasien BPJS Kesehatan', 'BPJS', "Pasien BPJS dilayani sesuai ketentuan BPJS Kesehatan.\n\n- Bawa kartu BPJS atau KIS yang aktif.\n- Bawa surat rujukan dari fasilitas kesehatan tingkat pertama (puskesmas atau klinik).\n- Bawa KTP dan kartu keluarga.\n- Untuk keadaan gawat darurat, rujukan tidak diperlukan."],
            ['Rawat inap', 'Rawat inap', "Pasien rawat inap berasal dari IGD atau dari poliklinik atas saran dokter.\n\n- Keluarga mengurus administrasi di loket rawat inap.\n- Siapkan identitas pasien dan kartu jaminan.\n- Bawa perlengkapan pribadi secukupnya.\n- Satu orang penunggu diperbolehkan per pasien."],
            ['Gawat darurat (IGD)', 'IGD', "IGD buka 24 jam setiap hari, termasuk hari libur.\n\n- Datang langsung ke IGD tanpa perlu mendaftar online.\n- Pasien ditangani berdasarkan tingkat kegawatan, bukan urutan kedatangan.\n- Hubungi nomor IGD untuk layanan ambulans."],
            ['Tata tertib besuk', 'Besuk', "Kunjungan pasien mengikuti jam besuk yang berlaku.\n\n- Patuhi jam besuk yang tertera di halaman Kontak.\n- Maksimal dua pengunjung bersamaan di kamar pasien.\n- Anak di bawah 12 tahun sebaiknya tidak ikut membesuk.\n- Jaga ketenangan dan kebersihan ruangan."],
        ];
        foreach ($guides as $i => [$title, $cat, $content]) {
            DB::table('guides')->insert(['title' => $title, 'category' => $cat, 'content' => $content, 'show' => true, 'sort' => $i + 1, 'created_at' => $now, 'updated_at' => $now]);
        }
        $faqs = [
            ['Apakah RSU Serenapita melayani pasien BPJS?', 'Ya. Kami melayani pasien BPJS Kesehatan sesuai ketentuan, serta pasien umum dan asuransi rekanan.', 'BPJS'],
            ['Bagaimana cara membuat janji dengan dokter?', 'Buka menu Buat Janji di situs ini, pilih poliklinik, dokter, tanggal, dan jam, lalu isi data diri. Anda akan mendapat kode janji temu dan nomor antrean.', 'Pendaftaran'],
            ['Apakah harus mendaftar online sebelum datang?', 'Tidak wajib. Anda tetap bisa mendaftar langsung di loket, tetapi pendaftaran online membantu mengurangi waktu tunggu.', 'Pendaftaran'],
            ['Kapan jam besuk pasien?', 'Jam besuk tertera di halaman Kontak. Mohon patuhi jam besuk agar pasien dapat beristirahat.', 'Rawat inap'],
            ['Apakah IGD buka 24 jam?', 'Ya. IGD buka 24 jam setiap hari, termasuk hari libur nasional.', 'IGD'],
            ['Bagaimana cara menyampaikan keluhan atau saran?', 'Gunakan halaman Pengaduan & Saran. Anda akan mendapat nomor tiket untuk memantau tanggapan kami.', 'Umum'],
        ];
        foreach ($faqs as $i => [$q, $a, $cat]) {
            DB::table('faqs')->insert(['question' => $q, 'answer' => $a, 'category' => $cat, 'show' => true, 'sort' => $i + 1, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        DB::table('menus')->where('route', 'about')->delete();
        foreach (['feedback', 'faqs', 'guides', 'gallery_items'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
