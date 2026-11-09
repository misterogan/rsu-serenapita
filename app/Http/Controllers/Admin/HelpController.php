<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Halaman Panduan di backoffice.
 * Isi diambil dari file Markdown di resources/docs (satu file per bab),
 * sehingga panduan selalu ikut ter-update setiap kali aplikasi di-deploy.
 */
class HelpController extends Controller
{
    public function __invoke()
    {
        return view('admin.help', self::load(resource_path('docs')));
    }

    /**
     * Baca bab panduan dari satu folder (urutan sesuai index.json di folder itu).
     *
     * @return array{chapters: array, updated: ?int}
     */
    public static function load(string $dir): array
    {
        // Bab yang tampil ditentukan index.json, supaya file lama yang tertinggal tidak ikut tampil
        $index = $dir.'/index.json';
        $list = is_file($index) ? json_decode((string) file_get_contents($index), true) : null;
        $files = is_array($list)
            ? array_values(array_filter(array_map(fn ($f) => $dir.'/'.basename((string) $f), $list), 'is_file'))
            : (glob($dir.'/*.md') ?: []);
        if (! is_array($list)) {
            sort($files);
        }
        $sig = md5($dir.'|'.collect($files)->map(fn ($f) => basename($f).filemtime($f))->implode('|'));

        $chapters = Cache::rememberForever('help.chapters.'.$sig, function () use ($files) {
            return collect($files)->map(function ($file) {
                $md = file_get_contents($file);
                preg_match('/^# (.+)$/m', $md, $m);
                preg_match('/<a id="([^"]+)" class="anchor"><\/a>/', $md, $a);

                return [
                    'id' => $a[1] ?? Str::slug(basename($file, '.md')),
                    'title' => trim($m[1] ?? basename($file, '.md')),
                    'html' => Str::markdown($md, ['html_input' => 'allow', 'allow_unsafe_links' => false]),
                ];
            })->values()->all();
        });

        return ['chapters' => $chapters, 'updated' => $files ? max(array_map('filemtime', $files)) : null];
    }
}
