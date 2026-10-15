<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slides', function (Blueprint $t) {
            $t->string('image_fit', 10)->default('auto'); // auto | cover | contain
        });
    }

    public function down(): void
    {
        Schema::table('slides', function (Blueprint $t) {
            $t->dropColumn('image_fit');
        });
    }
};
