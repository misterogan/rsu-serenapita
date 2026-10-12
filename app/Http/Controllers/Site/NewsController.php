<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $q = News::visible()->with('category');
        if ($cat = $request->query('category')) {
            $q->whereHas('category', fn ($c) => $c->where('name', $cat));
        }

        return view('site.news.index', [
            'news' => $q->paginate(9)->withQueryString(),
            'categories' => NewsCategory::whereHas('news', fn ($n) => $n->visible())->orderBy('name')->pluck('name'),
            'active' => $cat,
        ]);
    }

    public function show(string $slug)
    {
        $news = News::visible()->with('category')->where('slug', $slug)->firstOrFail();

        return view('site.news.show', ['news' => $news, 'more' => News::visible()->where('id', '!=', $news->id)->limit(3)->get()]);
    }
}
