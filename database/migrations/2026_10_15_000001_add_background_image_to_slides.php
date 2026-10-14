<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slides', function (Blueprint $t) {
            $t->string('image')->nullable();
            $t->string('image_position', 10)->default('center');
        });
    }

    public function down(): void
    {
        Schema::table('slides', function (Blueprint $t) {
            $t->dropColumn(['image', 'image_position']);
        });
    }
};
