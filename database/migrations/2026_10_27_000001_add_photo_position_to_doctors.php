<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $t) {
            // Bagian foto yang ditampilkan di lingkaran avatar: top | upper | center | lower
            $t->string('photo_position', 10)->default('top');
        });
    }

    public function down(): void
    {
        Schema::table('doctors', fn (Blueprint $t) => $t->dropColumn('photo_position'));
    }
};
