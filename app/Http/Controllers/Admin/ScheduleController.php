<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorLeave;
use App\Models\Poli;
use App\Support\SiteSettings;

class ScheduleController extends Controller
{
    public function __invoke()
    {
        $days = collect(range(0, 6))->map(fn ($i) => today()->addDays($i));
        $polis = Poli::with(['doctors' => fn ($q) => $q->where('status', '!=', 'Nonaktif')->with('leaves')->orderBy('name')])->orderBy('sort')->get();
        $leaves = DoctorLeave::with('doctor')->orderByDesc('date_from')->get();

        return view('admin.schedule', ['days' => $days, 'polis' => $polis, 'leaves' => $leaves, 'simrs' => SiteSettings::get('simrs')]);
    }
}
