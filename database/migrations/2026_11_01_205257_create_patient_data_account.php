<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Peran dan akun khusus menu Data pasien (dibaca dari SIMRS Khanza).
 * Peran ini hanya bisa membuka Data pasien. Kata sandi awal wajib diganti saat pertama kali masuk.
 */
return new class extends Migration
{
    public function up(): void
    {
        $perms = json_encode(['patients']);
        if ($role = DB::table('roles')->where('name', 'Data Pasien')->first()) {
            DB::table('roles')->where('id', $role->id)->update(['permissions' => $perms, 'updated_at' => now()]);
        } else {
            DB::table('roles')->insert(['name' => 'Data Pasien', 'permissions' => $perms, 'created_at' => now(), 'updated_at' => now()]);
        }
        if (! DB::table('users')->where('email', 'datapasien@rsuserenapita.id')->exists()) {
            DB::table('users')->insert([
                'name' => 'Petugas Data Pasien', 'email' => 'datapasien@rsuserenapita.id', 'password' => Hash::make('Pasien@Serenapita26'),
                'role_id' => DB::table('roles')->where('name', 'Data Pasien')->value('id'),
                'is_active' => true, 'must_change_password' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'datapasien@rsuserenapita.id')->delete();
        DB::table('roles')->where('name', 'Data Pasien')->delete();
    }
};
