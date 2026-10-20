<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Poli;

/** Halaman per poliklinik: membantu pasien (dan Google) menemukan "Poli Anak di Sidikalang" dan sejenisnya. */
class ClinicController extends Controller
{
    public function index()
    {
        $polis = Poli::where('active', true)->orderBy('sort')->orderBy('name')
            ->withCount(['doctors' => fn ($q) => $q->public()])->get();

        return view('site.clinics.index', ['polis' => $polis]);
    }

    public function show(string $slug)
    {
        $poli = Poli::findBySlug($slug);
        abort_unless($poli, 404);
        $doctors = $poli->doctors()->public()->with('leaves')->orderBy('name')->get();
        $others = Poli::where('active', true)->where('id', '!=', $poli->id)->orderBy('sort')->orderBy('name')->get();

        return view('site.clinics.show', ['poli' => $poli, 'doctors' => $doctors, 'others' => $others]);
    }
}
