<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Poli;
use App\Models\Slide;
use App\Support\SiteSettings;

class HomeController extends Controller
{
    public function __invoke()
    {
        $polis = Poli::where('active', true)->orderByDesc('featured')->orderBy('sort')->get();
        $news = News::visible()->with('category')->where('featured', true)->limit(4)->get();
        if ($news->count() < 4) {
            $news = $news->concat(News::visible()->with('category')->whereNotIn('id', $news->pluck('id'))->limit(4 - $news->count())->get());
        }

        return view('site.home', [
            'slides' => Slide::where('active', true)->orderBy('sort')->get(),
            'boxes' => SiteSettings::get('boxes'),
            'hours' => SiteSettings::get('hours'),
            'news' => $news,
            'featuredPoli' => $polis->firstWhere('featured', true) ?? $polis->first(),
            'polis' => $polis,
        ]);
    }
}
