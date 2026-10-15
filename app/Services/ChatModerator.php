<?php

namespace App\Services;

use App\Support\SiteSettings;

/**
 * Penyaring pesan live chat tanpa AI: kata kasar dan pesan asal-asalan.
 * Pesan yang tersaring tidak diteruskan ke petugas.
 */
class ChatModerator
{
    /** Kata yang selalu dianggap kasar. */
    public const HARD = [
        'kontol', 'kntl', 'memek', 'mmk', 'ngentot', 'ngentod', 'ngewe', 'entot', 'entod', 'jembut', 'peler', 'pepek', 'itil', 'colmek',
        'bangsat', 'bajingan', 'keparat', 'brengsek', 'kampret', 'jancok', 'jancuk', 'dancok', 'pantek', 'pukimak', 'puki', 'kimak', 'ngehe',
        'goblok', 'goblog', 'tolol', 'bego', 'idiot', 'dungu', 'bodat', 'lonte', 'pelacur', 'sundal', 'jalang', 'perek', 'bencong', 'banci',
        'fuck', 'fucking', 'fucker', 'motherfucker', 'shit', 'bitch', 'bastard', 'asshole', 'dick', 'pussy', 'cunt', 'wtf', 'stfu',
    ];

    /**
     * Kata yang juga punya arti biasa (misalnya "digigit anjing", "warna tai bayi").
     * Hanya dianggap kasar bila dipakai untuk memaki: dekat kata ganti orang, atau pesan sangat pendek.
     */
    public const SOFT = ['anjing', 'anjeng', 'anjg', 'njing', 'anjir', 'anjay', 'asu', 'babi', 'monyet', 'setan', 'iblis', 'tai', 'taik', 'sialan', 'sial', 'kurang ajar'];

    protected const PRONOUNS = ['lu', 'lo', 'loe', 'elu', 'elo', 'kau', 'engkau', 'kamu', 'kalian', 'situ', 'dasar', 'kayak', 'kek', 'emang', 'lah', 'mu', 'luh'];
    protected const SUFFIXES = ['nya', 'lah', 'mu', 'ku', 'lu', 'lo', 'loe', 'kau', 'an', 'in', 'kan', 'deh', 'dong', 'kah', 'nih', 'tuh', 'i', 'ny', 'nyo'];
    protected const PREFIXES = ['di', 'nge', 'ng', 'ke', 'se', 'me', 'ter'];
    protected const LEET = ['4' => 'a', '@' => 'a', '1' => 'i', '!' => 'i', '0' => 'o', '3' => 'e', '5' => 's', '$' => 's', '7' => 't', '8' => 'b', '9' => 'g'];
    protected const KEYBOARD = ['qwert', 'werty', 'ertyu', 'asdf', 'sdfg', 'dfgh', 'fghj', 'ghjk', 'hjkl', 'zxcv', 'xcvb', 'cvbn', 'vbnm', 'uiop', 'yuio', 'poiu', 'lkjh', 'mnbv', 'qazw', 'wsxe'];

    protected array $cfg;
    protected array $hard;
    protected array $soft;
    protected array $allow;

    public function __construct(?array $cfg = null)
    {
        $this->cfg = $cfg ?? SiteSettings::get('livechat');
        $extra = array_map([$this, 'squash'], $this->lines($this->cfg['block_words'] ?? ''));
        $this->allow = array_map([$this, 'squash'], $this->lines($this->cfg['allow_words'] ?? ''));
        $this->hard = array_values(array_diff(array_unique(array_merge(array_map([$this, 'squash'], self::HARD), $extra)), $this->allow));
        $this->soft = array_values(array_diff(array_map([$this, 'squash'], array_filter(self::SOFT, fn ($w) => ! str_contains($w, ' '))), $this->allow));
    }

    /**
     * Periksa teks. Mengembalikan null bila aman, atau
     * ['reason' => 'kasar'|'asal', 'message' => pesan untuk pengunjung, 'masked' => teks yang disamarkan].
     */
    public function check(string $text, string $kind = 'pesan'): ?array
    {
        $text = trim($text);
        if (($this->cfg['filter_profanity'] ?? true) && ($hits = $this->profanity($text))) {
            return ['reason' => 'kasar', 'masked' => $this->mask($text, $hits),
                'message' => $kind === 'nama' ? 'Nama mengandung kata yang tidak pantas.' : 'Pesan tidak terkirim karena mengandung kata yang tidak pantas. Mohon gunakan bahasa yang sopan.'];
        }
        if (($this->cfg['filter_spam'] ?? true) && $this->gibberish($text, $kind)) {
            return ['reason' => 'asal', 'masked' => mb_substr($text, 0, 200),
                'message' => $kind === 'nama' ? 'Mohon isi nama dengan benar.' : 'Pesan tidak terkirim karena tidak bisa dipahami. Mohon tulis pertanyaan Anda dengan jelas.'];
        }

        return null;
    }

    /** @return string[] token asli yang terdeteksi kasar */
    public function profanity(string $text): array
    {
        [$tokens, $raw] = $this->tokens($text);
        $hits = [];
        $wordCount = count(array_filter($tokens, fn ($t) => strlen($t) > 1));
        foreach ($tokens as $i => $tok) {
            if ($tok === '' || in_array($tok, $this->allow, true)) {
                continue;
            }
            if ($this->matches($tok, $this->hard)) {
                $hits[] = $raw[$i];

                continue;
            }
            if ($this->matches($tok, $this->soft)) {
                $near = array_slice($tokens, max(0, $i - 2), 5);
                $insult = $wordCount <= 3 || array_intersect($near, self::PRONOUNS)
                    || (bool) preg_match('/^\p{Lu}{3,}$/u', $raw[$i]) || str_contains($text, '!!');
                if ($insult) {
                    $hits[] = $raw[$i];
                }
            }
        }
        if (! $hits && preg_match('/kurang\s*ajar/i', $text) && preg_match('/\b(lu|lo|kau|kamu|kalian|dasar)\b/i', $text)) {
            $hits[] = 'kurang ajar';
        }

        return array_values(array_unique($hits));
    }

    /** Pesan asal-asalan: ketikan acak, huruf berulang, hanya simbol, atau tawa tanpa isi. */
    public function gibberish(string $text, string $kind = 'pesan'): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '') {
            return true;
        }
        if (preg_match('/(.)\1{5,}/u', $t)) {
            return true; // aaaaaa, ??????
        }
        $letters = preg_replace('/[^\p{L}]/u', '', $t);
        $digits = preg_replace('/\D/', '', $t);
        if ($letters === '' && $digits === '' && mb_strlen($t) >= 2) {
            return true; // hanya simbol
        }
        $words = array_values(array_filter(preg_split('/[^\p{L}]+/u', $t), fn ($w) => mb_strlen($w) > 0));
        if (! $words) {
            return $kind === 'nama';
        }
        $bad = 0;
        foreach ($words as $w) {
            $keyboard = false;
            foreach (self::KEYBOARD as $k) {
                if (str_contains($w, $k)) {
                    $keyboard = true;
                    break;
                }
            }
            $noVowel = mb_strlen($w) >= ($kind === 'nama' ? 4 : 7) && ! preg_match('/[aiueoy]/u', $w);
            $cluster = (bool) preg_match('/[^aiueoy\s]{7,}/u', $w);
            $laugh = (bool) preg_match('/^(wk|kw|ha|he|hi|xi|ak|ck)+[wkahexi]?$/u', $w) && mb_strlen($w) >= 6;
            if ($keyboard || $noVowel || $cluster || $laugh) {
                $bad++;
            }
        }

        return $bad / count($words) >= 0.5;
    }

    protected function matches(string $tok, array $list): bool
    {
        foreach ($list as $w) {
            if ($w === '') {
                continue;
            }
            if ($tok === $w) {
                return true;
            }
            if (str_starts_with($tok, $w) && in_array(substr($tok, strlen($w)), self::SUFFIXES, true)) {
                return true;
            }
            foreach (self::PREFIXES as $p) {
                if (str_starts_with($tok, $p.$w)) {
                    $rest = substr($tok, strlen($p.$w));
                    if ($rest === '' || in_array($rest, self::SUFFIXES, true)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /** @return array{0: string[], 1: string[]} token yang sudah dinormalkan dan token aslinya */
    protected function tokens(string $text): array
    {
        $parts = preg_split('/[\s,;:.\-_\/\\\\|"\'()\[\]{}?+=*~^`]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $norm = [];
        $raw = [];
        $run = [];
        $flush = function () use (&$run, &$norm, &$raw) {
            if (count($run) >= 3) {
                $joined = implode('', $run);
                $norm[] = $this->squash($joined);
                $raw[] = implode(' ', $run);
            } else {
                foreach ($run as $r) {
                    $norm[] = $this->squash($r);
                    $raw[] = $r;
                }
            }
            $run = [];
        };
        foreach ($parts as $p) {
            if (mb_strlen($p) === 1) {
                $run[] = $p;

                continue;
            }
            $flush();
            $norm[] = $this->squash($p);
            $raw[] = $p;
        }
        $flush();

        return [$norm, $raw];
    }

    /** Huruf kecil, angka/simbol pengganti huruf diubah, huruf berulang dirapatkan. */
    public function squash(string $w): string
    {
        $w = strtr(mb_strtolower(trim($w)), self::LEET);
        $w = preg_replace('/[^a-z]/', '', $w);

        return preg_replace('/(.)\1+/', '$1', $w);
    }

    protected function mask(string $text, array $hits): string
    {
        foreach ($hits as $h) {
            $letters = preg_split('//u', str_replace(' ', '', $h), -1, PREG_SPLIT_NO_EMPTY);
            $pattern = '/'.implode('[^\p{L}]*', array_map(fn ($c) => preg_quote($c, '/'), $letters)).'/iu';
            $text = preg_replace_callback($pattern, fn ($m) => mb_substr($m[0], 0, 1).str_repeat('*', max(2, mb_strlen($m[0]) - 1)), $text);
        }

        return mb_substr($text, 0, 200);
    }

    protected function lines(string $s): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $s))));
    }
}
