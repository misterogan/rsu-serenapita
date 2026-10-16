<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = ActivityLog::query()->latest('id');
        if (! $request->user()->isExecutive()) {
            $q->whereNotIn('user_id', \App\Models\User::whereIn('role_id', \App\Models\Role::executiveIds())->pluck('id'));
        }
        if ($term = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('action', 'like', "%$term%")->orWhere('user_name', 'like', "%$term%"));
        }

        return view('admin.logs', ['logs' => $q->paginate(50)->withQueryString()]);
    }
}
