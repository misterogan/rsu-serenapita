<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Peran Farmasi boleh membuka menu Cocokkan SIMRS. */
return new class extends Migration
{
    public function up(): void
    {
        $role = DB::table('roles')->where('name', 'Farmasi')->first();
        if ($role) {
            $perms = array_values(array_unique(array_merge(json_decode($role->permissions ?? '[]', true) ?: [], ['stock-compare'])));
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($perms), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $role = DB::table('roles')->where('name', 'Farmasi')->first();
        if ($role) {
            $perms = array_values(array_diff(json_decode($role->permissions ?? '[]', true) ?: [], ['stock-compare']));
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($perms), 'updated_at' => now()]);
        }
    }
};
