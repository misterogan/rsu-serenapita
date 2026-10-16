<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Modules;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return view('admin.roles', ['roles' => Role::withCount('users')->orderBy('id')->get()->reject->isExecutive()->values()]);
    }

    public function save(Request $request)
    {
        $valid = array_keys(Modules::all());
        $perm = $request->input('perm', []);
        foreach (Role::where('name', '!=', Role::ADMIN)->get()->reject->isExecutive() as $role) {
            $mods = array_values(array_intersect($valid, array_keys($perm[$role->id] ?? [])));
            $role->update(['permissions' => $mods]);
        }
        ActivityLog::record('Memperbarui hak akses peran');

        return back()->with('ok', 'Hak akses disimpan.');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:40|unique:roles,name']);
        Role::create(['name' => $data['name'], 'permissions' => ['dashboard']]);
        ActivityLog::record('Menambahkan peran '.$data['name']);

        return back()->with('ok', 'Peran ditambahkan.');
    }

    public function destroy(Role $role)
    {
        abort_if($role->isExecutive(), 404);
        if ($role->name === Role::ADMIN) {
            return back()->with('err', 'Peran Administrator tidak bisa dihapus.');
        }
        if ($role->users()->exists()) {
            return back()->with('err', 'Peran masih dipakai pengguna. Pindahkan pengguna dulu.');
        }
        $role->delete();
        ActivityLog::record('Menghapus peran '.$role->name);

        return back()->with('ok', 'Peran dihapus.');
    }
}
