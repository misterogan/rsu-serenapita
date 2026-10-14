<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\News;

/** Peta situs untuk mesin pencari (Google, Bing). */
class SitemapController extends Controller
{
    public function __invoke()
    {
        $lastDoctor = Doctor::max('updated_at');
        $pages = [
            ['loc' => route('home'), 'freq' => 'daily', 'prio' => '1.0', 'mod' => News::visible()->max('updated_at')],
            ['loc' => route('schedule'), 'freq' => 'daily', 'prio' => '0.9', 'mod' => $lastDoctor],
            ['loc' => route('doctors'), 'freq' => 'weekly', 'prio' => '0.9', 'mod' => $lastDoctor],
            ['loc' => route('appointment'), 'freq' => 'monthly', 'prio' => '0.8', 'mod' => null],
            ['loc' => route('services'), 'freq' => 'monthly', 'prio' => '0.7', 'mod' => null],
            ['loc' => route('contact'), 'freq' => 'monthly', 'prio' => '0.7', 'mod' => null],
            ['loc' => route('news.index'), 'freq' => 'daily', 'prio' => '0.8', 'mod' => News::visible()->max('updated_at')],
        ];
        foreach (News::visible()->get(['slug', 'updated_at']) as $n) {
            $pages[] = ['loc' => route('news.show', $n->slug), 'freq' => 'monthly', 'prio' => '0.6', 'mod' => $n->updated_at];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($pages as $p) {
            $xml .= '  <url><loc>'.e($p['loc']).'</loc>';
            if ($p['mod']) {
                $xml .= '<lastmod>'.\Illuminate\Support\Carbon::parse($p['mod'])->toAtomString().'</lastmod>';
            }
            $xml .= '<changefreq>'.$p['freq'].'</changefreq><priority>'.$p['prio'].'</priority></url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
