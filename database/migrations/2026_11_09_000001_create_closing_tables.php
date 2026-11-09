<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Closing akuntansi (data Khanza sampai cut off → COA baru → saldo awal SIMRS baru).
 * - closing_accounts: COA baru, diisi awal dari database/data/coa-serenavita.json (template COA Serenavita)
 * - closing_mappings: pemetaan rekening Khanza → akun COA baru
 * - closing_snapshots / closing_snapshot_data: salinan data Khanza per cut off, supaya closing tetap bisa
 *   dikerjakan walaupun server Khanza sudah dimatikan
 * - closing_adjustments / closing_adjustment_lines: jurnal penyesuaian dan koreksi dari Akunting
 * Peran Akunting mendapat hak mengubah (management:closing). Komisaris hanya bisa melihat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('closing_accounts', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name', 150);
            $t->string('parent_code', 30)->nullable();
            $t->string('type', 10)->default('detail'); // header | sub | detail
            $t->string('category', 30);                 // Aset, Kewajiban, Ekuitas, Pendapatan, Beban, Lain-lain
            $t->string('company', 80)->nullable();
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('closing_mappings', function (Blueprint $t) {
            $t->id();
            $t->string('kd_rek', 30)->unique();
            $t->string('coa_code', 30)->nullable();
            $t->string('user_name', 120)->nullable();
            $t->timestamps();
        });

        Schema::create('closing_snapshots', function (Blueprint $t) {
            $t->id();
            $t->date('cutoff');
            $t->date('year_start');
            $t->string('opening', 20);
            $t->json('errors')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('user_name', 120)->nullable();
            $t->timestamps();
        });

        Schema::create('closing_snapshot_data', function (Blueprint $t) {
            $t->id();
            $t->foreignId('snapshot_id')->constrained('closing_snapshots')->cascadeOnDelete();
            $t->string('dataset', 30);
            $t->longText('rows');
            $t->unique(['snapshot_id', 'dataset']);
        });

        Schema::create('closing_adjustments', function (Blueprint $t) {
            $t->id();
            $t->string('number', 30)->unique();
            $t->date('date');
            $t->string('description', 250);
            // penyesuaian = wajib seimbang; koreksi = boleh sepihak, untuk melengkapi jurnal Khanza yang tidak seimbang
            $t->string('kind', 12)->default('penyesuaian');
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('user_name', 120)->nullable();
            $t->timestamps();
        });

        Schema::create('closing_adjustment_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('adjustment_id')->constrained('closing_adjustments')->cascadeOnDelete();
            $t->string('coa_code', 30);
            $t->decimal('debit', 18, 2)->default(0);
            $t->decimal('credit', 18, 2)->default(0);
            $t->string('memo', 200)->nullable();
        });

        $file = database_path('data/coa-serenavita.json');
        if (is_file($file)) {
            $now = now();
            foreach (json_decode((string) file_get_contents($file), true) ?: [] as $i => $a) {
                DB::table('closing_accounts')->insert([
                    'code' => $a['code'], 'name' => $a['name'], 'parent_code' => $a['parent'], 'type' => $a['type'],
                    'category' => $a['category'], 'company' => $a['company'], 'sort' => $i + 1, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        if ($role = DB::table('roles')->where('name', 'Akunting')->first()) {
            $perms = array_values(array_unique(array_merge(json_decode($role->permissions ?? '[]', true) ?: [], ['management:finance', 'management:closing'])));
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($perms), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('closing_adjustment_lines');
        Schema::dropIfExists('closing_adjustments');
        Schema::dropIfExists('closing_snapshot_data');
        Schema::dropIfExists('closing_snapshots');
        Schema::dropIfExists('closing_mappings');
        Schema::dropIfExists('closing_accounts');
        if ($role = DB::table('roles')->where('name', 'Akunting')->first()) {
            $perms = array_values(array_diff(json_decode($role->permissions ?? '[]', true) ?: [], ['management:closing']));
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($perms)]);
        }
        DB::table('settings')->where('key', 'closing')->delete();
    }
};
