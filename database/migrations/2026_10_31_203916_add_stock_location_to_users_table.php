<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Petugas farmasi bisa dikunci ke satu unit (gudang / sentral); kosong berarti boleh mengisi semua unit.
 * Membuat akun petugas Gudang Farmasi dan Farmasi Sentral. Kata sandi awal wajib diganti saat pertama masuk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('stock_location', 20)->nullable()->after('role_id');
        });

        $roleId = DB::table('roles')->where('name', 'Farmasi')->value('id');
        $accounts = [
            ['gudang.farmasi@rsuserenapita.id', 'Petugas Gudang Farmasi', 'Gudang@Serenapita26', 'gudang'],
            ['farmasi.sentral@rsuserenapita.id', 'Petugas Farmasi Sentral', 'Sentral@Serenapita26', 'sentral'],
        ];
        foreach ($accounts as [$email, $name, $password, $location]) {
            if (! $roleId || DB::table('users')->where('email', $email)->exists()) {
                continue;
            }
            DB::table('users')->insert([
                'name' => $name, 'email' => $email, 'password' => Hash::make($password), 'role_id' => $roleId, 'stock_location' => $location,
                'is_active' => true, 'must_change_password' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')->whereIn('email', ['gudang.farmasi@rsuserenapita.id', 'farmasi.sentral@rsuserenapita.id'])->delete();
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('stock_location'));
    }
};
