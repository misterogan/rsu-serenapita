<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Peran dan akun Dasbor manajemen (Komisaris, Direktur, Akunting).
 * Akun ini tidak terlihat di menu Pengguna dan Hak akses milik pengelola konten.
 * Kata sandi awal wajib diganti saat pertama kali masuk.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', fn (Blueprint $t) => $t->boolean('must_change_password')->default(false));
        }
        $roles = [
            'Komisaris' => ['management:ops', 'management:finance'],
            'Direktur' => ['management:ops'],
            'Akunting' => ['management:finance'],
        ];
        $accounts = [
            ['Komisaris', 'komisaris@rsuserenapita.id', 'Komisaris RSU Serenapita', 'Komisaris@Serenapita26'],
            ['Direktur', 'direktur@rsuserenapita.id', 'Direktur RSU Serenapita', 'Direktur@Serenapita26'],
            ['Akunting', 'akunting@rsuserenapita.id', 'Akunting RSU Serenapita', 'Akunting@Serenapita26'],
        ];
        foreach ($roles as $name => $perms) {
            $exists = DB::table('roles')->where('name', $name)->first();
            if ($exists) {
                DB::table('roles')->where('id', $exists->id)->update(['permissions' => json_encode($perms), 'updated_at' => now()]);
            } else {
                DB::table('roles')->insert(['name' => $name, 'permissions' => json_encode($perms), 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        foreach ($accounts as [$role, $email, $name, $password]) {
            if (DB::table('users')->where('email', $email)->exists()) {
                continue;
            }
            DB::table('users')->insert([
                'name' => $name, 'email' => $email, 'password' => Hash::make($password),
                'role_id' => DB::table('roles')->where('name', $role)->value('id'),
                'is_active' => true, 'must_change_password' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')->whereIn('email', ['komisaris@rsuserenapita.id', 'direktur@rsuserenapita.id', 'akunting@rsuserenapita.id'])->delete();
        DB::table('roles')->whereIn('name', ['Komisaris', 'Direktur', 'Akunting'])->delete();
    }
};
