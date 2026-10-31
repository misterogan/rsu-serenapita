<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Persediaan obat farmasi (pengganti file Excel PERSEDIAAN OBAT UNIT FARMASI / GUDANG FARMASI).
 * Satu baris stock_entries = isian satu obat, di satu unit, pada satu tanggal: masuk, keluar, dan hasil stock opname.
 * Daftar obat awal diambil dari database/data/obat.json (hasil salinan Excel Oktober 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $t) {
            $t->id();
            $t->string('name', 150);
            $t->string('group', 40)->index();
            $t->string('unit', 30);
            $t->decimal('price', 14, 2)->nullable();
            $t->date('expired_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('stock_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('medicine_id')->constrained()->cascadeOnDelete();
            $t->string('location', 20);
            $t->date('date');
            $t->unsignedInteger('qty_in')->default(0);
            $t->unsignedInteger('qty_out')->default(0);
            $t->unsignedInteger('counted')->nullable();
            $t->string('note', 150)->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
            $t->unique(['medicine_id', 'location', 'date']);
            $t->index(['location', 'date']);
        });

        $file = database_path('data/obat.json');
        if (is_file($file)) {
            $now = now();
            $rows = array_map(fn ($m) => [
                'name' => $m['name'], 'group' => $m['group'], 'unit' => $m['unit'], 'price' => $m['price'],
                'expired_at' => $m['expired_at'], 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ], json_decode((string) file_get_contents($file), true) ?: []);
            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table('medicines')->insert($chunk);
            }
        }

        $perms = json_encode(['stock', 'stock-report', 'medicines']);
        if ($role = DB::table('roles')->where('name', 'Farmasi')->first()) {
            DB::table('roles')->where('id', $role->id)->update(['permissions' => $perms, 'updated_at' => now()]);
        } else {
            DB::table('roles')->insert(['name' => 'Farmasi', 'permissions' => $perms, 'created_at' => now(), 'updated_at' => now()]);
        }
        if (! DB::table('users')->where('email', 'farmasi@rsuserenapita.id')->exists()) {
            DB::table('users')->insert([
                'name' => 'Petugas Farmasi', 'email' => 'farmasi@rsuserenapita.id', 'password' => Hash::make('Farmasi@Serenapita26'),
                'role_id' => DB::table('roles')->where('name', 'Farmasi')->value('id'),
                'is_active' => true, 'must_change_password' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_entries');
        Schema::dropIfExists('medicines');
        DB::table('users')->where('email', 'farmasi@rsuserenapita.id')->delete();
        DB::table('roles')->where('name', 'Farmasi')->delete();
    }
};
