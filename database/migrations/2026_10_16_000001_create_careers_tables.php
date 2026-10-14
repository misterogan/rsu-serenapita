<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_openings', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->string('department')->nullable();
            $t->string('employment_type')->default('Kontrak');
            $t->string('education')->nullable();
            $t->string('location')->default('Sidikalang');
            $t->unsignedInteger('quota')->nullable();
            $t->date('deadline')->nullable();
            $t->string('status')->default('Draf'); // Dibuka | Ditutup | Draf
            $t->text('summary')->nullable();
            $t->longText('description')->nullable();
            $t->longText('requirements')->nullable();
            $t->longText('benefits')->nullable();
            $t->timestamps();
        });

        Schema::create('job_applications', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->foreignId('job_opening_id')->constrained('job_openings')->cascadeOnDelete();
            $t->string('name');
            $t->string('email');
            $t->string('phone', 20);
            $t->date('birth_date')->nullable();
            $t->string('education');
            $t->string('major')->nullable();
            $t->string('institution')->nullable();
            $t->unsignedTinyInteger('experience_years')->default(0);
            $t->string('city')->nullable();
            $t->text('cover_letter')->nullable();
            $t->string('cv_path');
            $t->string('doc_path')->nullable();
            $t->string('status')->default('Baru'); // Baru | Diproses | Wawancara | Diterima | Ditolak
            $t->text('note')->nullable();
            $t->timestamps();
            $t->index(['job_opening_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_openings');
    }
};
