<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\DoctorLeave;
use App\Models\Poli;
use App\Models\Service;
use App\Support\SiteSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Chatbot Serena berbasis aturan (tanpa layanan AI luar).
 * Semua jawaban dibentuk dari data di database: dokter, jadwal & cuti dari SIMRS,
 * poli, layanan, profil & kontak, serta FAQ yang diisi di backoffice.
 */
class Chatbot
{
    /** Singkatan & ejaan santai yang sering dipakai pengunjung. */
    protected const NORMALIZE = [
        'dktr' => 'dokter', 'dr' => 'dokter', 'jdwl' => 'jadwal', 'jdw' => 'jadwal', 'bsk' => 'besok', 'bsok' => 'besok',
        'hr' => 'hari', 'gk' => 'tidak', 'ga' => 'tidak', 'gak' => 'tidak', 'nggak' => 'tidak', 'engga' => 'tidak', 'enggak' => 'tidak',
        'tdk' => 'tidak', 'yg' => 'yang', 'utk' => 'untuk', 'brp' => 'berapa', 'jm' => 'jam', 'praktek' => 'praktik', 'prakter' => 'praktik',
        'sy' => 'saya', 'aja' => 'saja', 'gmn' => 'bagaimana', 'gimana' => 'bagaimana', 'dmn' => 'dimana', 'mana' => 'mana', 'no' => 'nomor',
        'telp' => 'telepon', 'tlp' => 'telepon', 'hp' => 'telepon', 'wa' => 'whatsapp', 'syaraf' => 'saraf', 'apotik' => 'apotek',
        'jumat' => 'jumat', "jum'at" => 'jumat', 'mnggu' => 'minggu', 'snin' => 'senin',
    ];

    /** Kata kunci tambahan per poli, dicocokkan dengan kata pada nama poli. */
    protected const POLI_SYNONYMS = [
        'anak' => ['anak', 'bayi', 'balita', 'imunisasi', 'tumbuh', 'kembang'],
        'kandungan' => ['kandungan', 'kebidanan', 'hamil', 'kehamilan', 'bidan', 'obgyn', 'usg', 'melahirkan', 'persalinan', 'haid', 'menstruasi'],
        'mata' => ['mata', 'katarak', 'kacamata', 'rabun', 'minus'],
        'gigi' => ['gigi', 'gusi', 'karang', 'tambal', 'cabut'],
        'tht' => ['tht', 'telinga', 'hidung', 'tenggorokan', 'amandel', 'sinus', 'sinusitis'],
        'saraf' => ['saraf', 'neurologi', 'stroke', 'vertigo', 'migrain', 'kesemutan'],
        'dalam' => ['dalam', 'diabetes', 'kencing', 'hipertensi', 'tensi', 'lambung', 'maag', 'ginjal', 'kolesterol', 'asam'],
        'bedah' => ['bedah', 'operasi', 'hernia', 'benjolan', 'usus', 'tumor'],
        'fisioterapi' => ['fisioterapi', 'fisio', 'terapi', 'rehabilitasi', 'sendi', 'otot'],
        'umum' => ['umum', 'keterangan', 'sehat'],
        'jantung' => ['jantung', 'kardiologi'],
        'paru' => ['paru', 'batuk', 'asma', 'tbc'],
        'kulit' => ['kulit', 'kelamin', 'jerawat', 'gatal', 'alergi'],
        'jiwa' => ['jiwa', 'psikiatri', 'cemas', 'depresi'],
    ];

    protected const SERVICE_SYNONYMS = [
        'laboratorium' => ['lab', 'laboratorium', 'cek darah', 'tes darah', 'urine'],
        'radiologi' => ['radiologi', 'rontgen', 'ronsen', 'usg', 'x-ray'],
        'farmasi' => ['farmasi', 'apotek', 'obat', 'resep'],
        'rawat inap' => ['rawat inap', 'kamar', 'opname', 'vip', 'kelas'],
        'ambulans' => ['ambulans', 'ambulan', 'jemput pasien'],
        'bersalin' => ['bersalin', 'melahirkan', 'persalinan'],
        'operasi' => ['kamar operasi', 'ruang operasi'],
        'igd' => ['igd', 'ugd', 'gawat darurat'],
    ];

    protected const EMERGENCY = ['darurat', 'gawat', 'pingsan', 'tidak sadar', 'kecelakaan', 'pendarahan', 'perdarahan', 'nyeri dada', 'sakit dada', 'sesak napas', 'sesak nafas', 'kejang', 'keracunan', 'overdosis', 'mau melahirkan', 'ketuban pecah'];

    protected array $profile;
    protected array $bot;

    public function answer(string $question, array $history = []): string
    {
        $this->profile = SiteSettings::profile();
        $this->bot = SiteSettings::get('chatbot');
        $q = $this->normalize($question);
        $parts = [];

        if ($this->has($q, self::EMERGENCY)) {
            $parts[] = $this->emergencyText();
        }

        $answer = $this->route($q, $history);
        if ($answer) {
            $parts[] = $answer;
        }

        if (! $parts) {
            return $this->fallbackText();
        }

        return implode("\n\n", array_unique($parts));
    }

    /* ---------------------------------------------------------------- */

    protected function route(string $q, array $history): ?string
    {
        // Terima kasih
        if (preg_match('/\b(terima ?kasih|makasih|thanks|trims|mauliate)\b/', $q)) {
            return 'Sama-sama. Semoga lekas sehat. Ada lagi yang bisa saya bantu?';
        }

        // FAQ dari backoffice didahulukan agar petugas bisa menimpa jawaban bawaan
        if ($faq = $this->faq($q)) {
            return $faq;
        }

        $date = $this->dateFrom($q);
        $doctor = $this->findDoctor($q);
        $poli = $doctor ? null : $this->findPoli($q);

        // Pertanyaan lanjutan seperti "kalau senin?" memakai dokter/poli dari pesan sebelumnya
        if (! $doctor && ! $poli && $date && $history) {
            foreach (array_reverse($history) as $h) {
                if (($h['role'] ?? '') !== 'user' || ! is_string($h['content'] ?? null)) {
                    continue;
                }
                $prev = $this->normalize($h['content']);
                if ($doctor = $this->findDoctor($prev)) {
                    break;
                }
                if ($poli = $this->findPoli($prev)) {
                    break;
                }
            }
        }

        if ($doctor) {
            return $this->doctorAnswer($doctor, $date);
        }
        if ($poli) {
            return $this->poliAnswer($poli, $date);
        }

        if ($this->has($q, ['cuti', 'libur', 'tidak praktik', 'tidak masuk'])) {
            return $this->leavesAnswer();
        }
        if ($date && $this->has($q, ['dokter', 'praktik', 'jadwal', 'buka'])) {
            return $this->dayAnswer($date);
        }
        if ($this->has($q, ['daftar dokter', 'dokter apa saja', 'dokter siapa saja', 'semua dokter', 'dokter spesialis', 'ada dokter apa'])) {
            return $this->doctorList();
        }
        if ($this->has($q, ['poli apa', 'poliklinik apa', 'daftar poli', 'ada poli', 'spesialis apa', 'layanan apa', 'fasilitas apa'])) {
            return $this->poliAndServiceList();
        }
        if ($service = $this->findService($q)) {
            return $service->name.': '.$service->description;
        }
        if ($this->has($q, ['besuk', 'jenguk', 'membesuk', 'menjenguk'])) {
            return 'Jam besuk: '.$this->profile['visiting_hours'].'. Anak di bawah 12 tahun sebaiknya tidak ikut membesuk.';
        }
        if ($this->has($q, ['bpjs', 'rujukan', 'jkn', 'kis'])) {
            return "Pasien BPJS Kesehatan perlu membawa:\n• KTP\n• Kartu BPJS/KIS\n• Surat rujukan yang masih berlaku dari puskesmas atau klinik tingkat pertama\nUntuk keadaan darurat di IGD tidak perlu rujukan. Daftar poli bisa lewat menu Buat Janji atau loket pendaftaran.";
        }
        if ($this->has($q, ['janji', 'daftar', 'booking', 'antre', 'antri', 'pendaftaran online', 'reservasi']) && ! \App\Support\Links::enabled('appointment')) {
            return 'Pendaftaran online sedang tidak tersedia. Silakan daftar di loket pendaftaran ('.$this->profile['counter_hours'].') atau hubungi kami:'."\n".$this->contactAnswer();
        }
        if ($this->has($q, ['janji', 'daftar', 'booking', 'antre', 'antri', 'pendaftaran online', 'reservasi'])) {
            return "Cara membuat janji temu:\n1. Buka menu Buat Janji\n2. Pilih poli, dokter, tanggal, dan jam\n3. Isi data pasien lalu kirim\nAnda akan mendapat kode janji dan nomor antrean. Datang ke loket 15 menit sebelum jamnya.\nLoket pendaftaran: ".$this->profile['counter_hours'].'.';
        }
        if ($this->has($q, ['loket', 'jam buka', 'jam operasional', 'jam pelayanan', 'buka jam', 'tutup jam', 'buka hari'])) {
            return 'Loket pendaftaran: '.$this->profile['counter_hours'].'. IGD buka 24 jam setiap hari.';
        }
        if ($this->has($q, ['alamat', 'lokasi', 'dimana', 'di mana', 'arah', 'rute', 'maps', 'peta'])) {
            return 'Kami berada di '.SiteSettings::address().'.'.($this->profile['maps'] ? "\nPeta: ".$this->profile['maps'] : '');
        }
        if ($this->has($q, ['telepon', 'nomor', 'kontak', 'hubungi', 'whatsapp', 'email', 'call center'])) {
            return $this->contactAnswer();
        }
        if ($this->has($q, ['igd', 'ugd'])) {
            return $this->emergencyText();
        }
        if ($this->has($q, ['dokter', 'jadwal', 'praktik'])) {
            return 'Sebutkan nama dokter atau polinya, misalnya "jadwal dokter anak" atau "dr. Rahmat praktik hari apa". Jadwal lengkap ada di menu Jadwal.';
        }
        // Sapaan saja (tanpa pertanyaan lain)
        if (preg_match('/^(halo|hai|hi|hello|selamat (pagi|siang|sore|malam)|permisi|assalamualaikum|horas)\b/', trim($q))) {
            return $this->bot['greeting'];
        }

        return null;
    }

    /* ---------------- Pencocokan ---------------- */

    protected function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^\p{L}\p{N}\s\'\-\.:]/u', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $words = array_map(fn ($w) => self::NORMALIZE[rtrim($w, '.')] ?? $w, $words);

        return ' '.implode(' ', $words).' ';
    }

    protected function has(string $q, array $needles): bool
    {
        foreach ($needles as $n) {
            if (str_contains($q, ' '.$n.' ') || str_contains($q, ' '.$n) && mb_strlen($n) >= 5) {
                return true;
            }
        }

        return false;
    }

    protected function words(string $q): array
    {
        return array_values(array_filter(explode(' ', trim(str_replace(['.', ':'], ' ', $q)))));
    }

    /** Kata mirip (salah ketik 1 huruf untuk kata ≥ 5 huruf). */
    protected function similar(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        return mb_strlen($a) >= 5 && mb_strlen($b) >= 5 && levenshtein($a, $b) <= 1;
    }

    protected function findDoctor(string $q): ?Doctor
    {
        $words = $this->words($q);
        $best = null;
        $bestScore = 0;
        foreach (Doctor::public()->with(['poli', 'leaves'])->get() as $d) {
            $name = mb_strtolower(preg_replace('/\b(dr|drg|ns|sp|br|s)\.[\w\-\.]*|,.*$/iu', ' ', $d->name));
            $tokens = array_filter(preg_split('/\s+/', $name), fn ($t) => mb_strlen($t) >= 4);
            $score = 0;
            foreach ($tokens as $t) {
                foreach ($words as $w) {
                    if ($this->similar($w, $t)) {
                        $score++;
                        break;
                    }
                }
            }
            if ($score > $bestScore) {
                [$best, $bestScore] = [$d, $score];
            }
        }

        return $best;
    }

    protected function findPoli(string $q): ?Poli
    {
        $words = $this->words($q);
        foreach (Poli::where('active', true)->orderBy('sort')->get() as $p) {
            $nameWords = array_filter(explode(' ', mb_strtolower(str_replace(['Poli', '&', ','], ' ', $p->name))), fn ($w) => mb_strlen($w) >= 3);
            $keys = $nameWords;
            foreach (self::POLI_SYNONYMS as $root => $syn) {
                if (in_array($root, $nameWords, true) || ($root === 'kandungan' && in_array('kebidanan', $nameWords, true))) {
                    $keys = array_merge($keys, $syn);
                }
            }
            foreach ($keys as $k) {
                if (str_contains($k, ' ') ? str_contains($q, ' '.$k.' ') : collect($words)->contains(fn ($w) => $this->similar($w, $k))) {
                    // "umum" terlalu luas; hanya cocok bila disebut bersama kata poli/dokter
                    if ($k === 'umum' && ! $this->has($q, ['poli', 'dokter'])) {
                        continue;
                    }

                    return $p;
                }
            }
        }

        return null;
    }

    protected function findService(string $q): ?Service
    {
        $services = Service::where('show', true)->get();
        foreach (self::SERVICE_SYNONYMS as $root => $syn) {
            if ($this->has($q, $syn)) {
                if ($s = $services->first(fn ($s) => str_contains(mb_strtolower($s->name), $root))) {
                    return $s;
                }
            }
        }
        foreach ($services as $s) {
            foreach (explode(' ', mb_strtolower($s->name)) as $w) {
                if (mb_strlen($w) >= 5 && $this->has($q, [$w])) {
                    return $s;
                }
            }
        }

        return null;
    }

    /** FAQ di backoffice: satu baris per entri, format "kata kunci, kata kunci: jawaban". */
    protected function faq(string $q): ?string
    {
        foreach (preg_split('/\R/', (string) ($this->bot['faq'] ?? '')) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }
            [$keys, $answer] = array_map('trim', explode(':', $line, 2));
            $keys = array_filter(array_map(fn ($k) => mb_strtolower(trim($k)), explode(',', $keys)));
            if ($answer !== '' && $keys && $this->has($q, $keys)) {
                return $answer;
            }
        }

        return null;
    }

    protected function dateFrom(string $q): ?Carbon
    {
        if ($this->has($q, ['hari ini', 'sekarang', 'saat ini'])) {
            return today();
        }
        if ($this->has($q, ['lusa'])) {
            return today()->addDays(2);
        }
        if ($this->has($q, ['besok'])) {
            return today()->addDay();
        }
        foreach (Doctor::DAYS as $i => $day) {
            if ($this->has($q, [mb_strtolower($day)])) {
                $d = today();
                while ($d->dayOfWeek !== $i) {
                    $d->addDay();
                }

                return $d;
            }
        }

        return null;
    }

    /* ---------------- Jawaban ---------------- */

    protected function dayName(Carbon $d): string
    {
        $label = Doctor::DAYS[$d->dayOfWeek].', '.$d->translatedFormat('d F');
        if ($d->isToday()) {
            return 'hari ini ('.$label.')';
        }
        if ($d->isTomorrow()) {
            return 'besok ('.$label.')';
        }

        return $label;
    }

    protected function doctorAnswer(Doctor $d, ?Carbon $date): string
    {
        $poli = $d->poli?->name ? ' ('.$d->poli->name.')' : '';
        $leave = $d->leaves->first(fn ($l) => $l->date_to->gte(today()));

        if ($date) {
            if ($l = $d->onLeave($date)) {
                return "{$d->name}{$poli} sedang cuti {$this->dayName($date)}, sampai {$l->date_to->translatedFormat('d F Y')}. Jadwal biasanya: {$d->scheduleText()}.";
            }
            $hours = $d->hoursText($date->dayOfWeek);
            if (! $hours) {
                return "{$d->name}{$poli} tidak praktik {$this->dayName($date)}. Jadwal praktiknya: {$d->scheduleText()}.";
            }

            return "{$d->name}{$poli} praktik {$this->dayName($date)} pukul {$hours}.".(\App\Support\Links::enabled('appointment') ? ' Buat janji lewat menu Buat Janji agar tidak antre lama.' : '');
        }

        $text = "Jadwal {$d->name}{$poli}:\n".collect($d->schedule ?? [])->sortBy(fn ($s) => (($s[0] + 6) % 7).$s[1])->map(fn ($s) => '• '.Doctor::DAYS[$s[0]].' '.$s[1].'–'.$s[2])->implode("\n");
        if ($leave) {
            $text .= "\nCatatan: sedang/akan cuti {$leave->date_from->translatedFormat('d M')} – {$leave->date_to->translatedFormat('d M Y')}.";
        }

        return $text;
    }

    protected function poliAnswer(Poli $p, ?Carbon $date): string
    {
        $doctors = $p->doctors()->public()->with('leaves')->orderBy('name')->get();
        if ($doctors->isEmpty()) {
            return "{$p->name} saat ini belum memiliki jadwal dokter di situs. Silakan hubungi bagian pendaftaran.";
        }

        if ($date) {
            $lines = $doctors->map(function ($d) use ($date) {
                $hours = $d->hoursText($date->dayOfWeek);
                if (! $hours) {
                    return null;
                }

                return '• '.$d->name.' '.$hours.($d->onLeave($date) ? ' (cuti)' : '');
            })->filter();

            return $lines->isEmpty()
                ? "{$p->name} tidak ada jadwal praktik {$this->dayName($date)}."
                : "{$p->name}, {$this->dayName($date)}:\n".$lines->implode("\n");
        }

        return "{$p->name}:\n".$doctors->map(fn ($d) => '• '.$d->name.': '.$d->scheduleText().($d->leaves->first(fn ($l) => $l->date_to->gte(today())) ? ' (ada jadwal cuti)' : ''))->implode("\n")
            .($p->description ? "\nMelayani: ".\App\Support\Rich::plain($p->description, 300) : '');
    }

    protected function dayAnswer(Carbon $date): string
    {
        $lines = Doctor::public()->with(['poli', 'leaves'])->orderBy('name')->get()
            ->filter(fn ($d) => $d->slotFor($date->dayOfWeek) && ! $d->onLeave($date))
            ->groupBy(fn ($d) => $d->poli?->name)
            ->map(fn (Collection $docs, $poli) => $poli.': '.$docs->map(fn ($d) => $d->name.' '.$d->hoursText($date->dayOfWeek))->implode('; '));

        return $lines->isEmpty()
            ? 'Tidak ada praktik poliklinik '.$this->dayName($date).'. IGD tetap buka 24 jam.'
            : 'Dokter yang praktik '.$this->dayName($date).":\n• ".$lines->implode("\n• ");
    }

    protected function leavesAnswer(): string
    {
        $leaves = DoctorLeave::with('doctor')->whereDate('date_to', '>=', today())->orderBy('date_from')->get();

        return $leaves->isEmpty()
            ? 'Saat ini tidak ada dokter yang dijadwalkan cuti.'
            : "Dokter yang cuti:\n".$leaves->map(fn ($l) => '• '.$l->doctor?->name.': '.$l->date_from->translatedFormat('d M').' – '.$l->date_to->translatedFormat('d M Y'))->implode("\n");
    }

    protected function doctorList(): string
    {
        return "Dokter kami:\n".Poli::where('active', true)->orderBy('sort')->with(['doctors' => fn ($q) => $q->public()->orderBy('name')])->get()
            ->filter(fn ($p) => $p->doctors->isNotEmpty())
            ->map(fn ($p) => '• '.$p->name.': '.$p->doctors->pluck('name')->implode(', '))->implode("\n")
            ."\nTanyakan nama dokter untuk melihat jadwalnya.";
    }

    protected function poliAndServiceList(): string
    {
        $polis = Poli::where('active', true)->orderBy('sort')->pluck('name')->implode(', ');
        $services = Service::where('show', true)->orderBy('sort')->pluck('name')->implode(', ');

        return "Poliklinik: {$polis}.\nLayanan lain: {$services}.";
    }

    protected function contactAnswer(): string
    {
        $phones = SiteSettings::phones('contact');
        $lines = $phones->map(fn ($p) => '• '.$p->label.': '.$p->number.($p->kind === 'wa' ? ' (WhatsApp)' : ''));
        if ($this->profile['email']) {
            $lines->push('• Email: '.$this->profile['email']);
        }

        return "Kontak {$this->profile['name']}:\n".$lines->implode("\n");
    }

    protected function emergencyText(): string
    {
        $igd = SiteSettings::emergencyPhone();

        return trim($this->bot['emergency'].($igd ? ' Telepon IGD: '.$igd->number.'.' : ''));
    }

    protected function fallbackText(): string
    {
        $text = $this->bot['fallback'] ?? '';
        if ($text) {
            return $text;
        }
        $igd = SiteSettings::emergencyPhone();

        return "Maaf, saya belum memahami pertanyaan itu. Saya bisa membantu soal:\n• jadwal dokter atau poli (contoh: \"jadwal dokter anak hari sabtu\")\n• dokter yang cuti\n• cara daftar BPJS dan buat janji\n• jam besuk, alamat, dan kontak\nUntuk pertanyaan lain, tekan tombol \"Chat dengan petugas\" di bawah".($igd ? " atau hubungi {$igd->number}." : '.');
    }
}
