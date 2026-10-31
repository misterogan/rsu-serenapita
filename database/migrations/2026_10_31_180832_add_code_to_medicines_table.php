<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Kode obat SIMRS sebagai kunci, supaya stok bisa dicocokkan per obat dengan SIMRS (nama di SIMRS boleh kembar). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $t) {
            $t->string('code', 40)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $t) {
            $t->dropUnique(['code']);
            $t->dropColumn('code');
        });
    }
};
