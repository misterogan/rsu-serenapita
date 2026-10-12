<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Poli;
use App\Models\Service;

class PageController extends Controller
{
    public function services()
    {
        return view('site.services', [
            'services' => Service::where('show', true)->orderBy('sort')->get(),
            'polis' => Poli::where('active', true)->orderBy('sort')->get(),
        ]);
    }

    public function contact()
    {
        return view('site.contact');
    }
}
