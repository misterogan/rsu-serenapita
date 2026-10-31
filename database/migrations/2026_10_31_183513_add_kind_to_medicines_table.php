<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Jenis barang farmasi: obat atau BMHP (bahan medis habis pakai), supaya daftar dan stoknya terpisah. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $t) {
            $t->string('kind', 10)->default('obat')->index()->after('code');
        });

        $role = DB::table('roles')->where('name', 'Farmasi')->first();
        if ($role) {
            $perms = array_values(array_unique(array_merge(json_decode($role->permissions ?? '[]', true) ?: [], ['bmhp'])));
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($perms), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $t) {
            $t->dropIndex(['kind']);
            $t->dropColumn('kind');
        });
    }
};
