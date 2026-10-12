<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\ChatConversation;
use App\Models\Doctor;
use App\Models\DoctorLeave;
use App\Models\News;
use App\Models\Poli;
use App\Support\SiteSettings;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = today();
        $todayAppts = Appointment::whereDate('date', $today)->where('status', '!=', 'Batal')->get();
        $week = Appointment::with('doctor')->whereBetween('date', [$today, $today->copy()->addDays(6)])->where('status', '!=', 'Batal')->get();
        $perPoli = Poli::orderBy('sort')->get()->map(fn ($p) => [$p->name, $week->filter(fn ($a) => $a->doctor?->poli_id === $p->id)->count()])
            ->filter(fn ($x) => $x[1] > 0)->sortByDesc(1)->values();

        return view('admin.dashboard', [
            'stats' => [
                'today' => $todayAppts->count(),
                'present' => $todayAppts->where('status', 'Hadir')->count(),
                'chats' => ChatConversation::where('status', 'menunggu')->count(),
                'chatsActive' => ChatConversation::where('status', 'aktif')->count(),
                'doctors' => Doctor::where('status', 'Aktif')->count(),
                'doctorsAll' => Doctor::count(),
                'news' => News::where('status', 'Terbit')->count(),
                'drafts' => News::where('status', 'Draf')->count(),
            ],
            'upcoming' => Appointment::with('doctor')->whereDate('date', '>=', $today)->whereNotIn('status', ['Batal', 'Selesai'])->orderBy('date')->orderBy('time')->limit(6)->get(),
            'perPoli' => $perPoli,
            'max' => max(1, $perPoli->max(1) ?? 1),
            'leaves' => DoctorLeave::with('doctor')->whereDate('date_to', '>=', $today)->orderBy('date_from')->get(),
            'logs' => ActivityLog::latest('id')->limit(6)->get(),
            'simrs' => SiteSettings::get('simrs'),
        ]);
    }
}
