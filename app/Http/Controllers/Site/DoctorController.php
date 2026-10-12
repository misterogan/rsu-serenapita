<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Poli;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $q = Doctor::public()->with(['poli', 'leaves'])->orderBy('name');
        if ($poli = $request->query('clinic')) {
            $q->where('poli_id', $poli);
        }
        if ($term = trim((string) $request->query('q'))) {
            $q->where('name', 'like', "%$term%");
        }

        return view('site.doctors', [
            'doctors' => $q->get(),
            'polis' => Poli::where('active', true)->orderBy('sort')->get(),
        ]);
    }

    public function schedule()
    {
        $polis = Poli::where('active', true)->orderBy('sort')
            ->with(['doctors' => fn ($q) => $q->public()->with('leaves')->orderBy('name')])->get()
            ->filter(fn ($p) => $p->doctors->isNotEmpty());

        return view('site.schedule', ['polis' => $polis, 'days' => collect(range(0, 6))->map(fn ($i) => today()->addDays($i))]);
    }
}
