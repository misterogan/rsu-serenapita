<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->json('permissions')->nullable();
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->unsignedBigInteger('role_id')->nullable()->index();
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_login_at')->nullable();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->json('value')->nullable();
            $t->timestamps();
        });

        Schema::create('phones', function (Blueprint $t) {
            $t->id();
            $t->string('label');
            $t->string('number');
            $t->string('kind')->default('tel'); // tel | wa
            $t->boolean('show_top')->default(false);
            $t->boolean('show_igd')->default(false);
            $t->boolean('show_contact')->default(true);
            $t->boolean('show_footer')->default(false);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('menus', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->string('label');
            $t->string('route');
            $t->boolean('visible')->default(true);
            $t->boolean('locked')->default(false);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('news_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->timestamps();
        });

        Schema::create('news', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->foreignId('category_id')->nullable()->constrained('news_categories')->nullOnDelete();
            $t->date('published_at');
            $t->string('status')->default('Draf'); // Terbit | Draf | Terjadwal
            $t->string('illustration')->default('heart');
            $t->string('image')->nullable();
            $t->text('excerpt');
            $t->longText('body')->nullable();
            $t->boolean('featured')->default(false);
            $t->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('slides', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('text')->nullable();
            $t->string('tab')->nullable();
            $t->string('button')->nullable();
            $t->string('link')->nullable();
            $t->boolean('active')->default(true);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('icon')->default('cross');
            $t->boolean('show')->default(true);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('polis', function (Blueprint $t) {
            $t->id();
            $t->string('simrs_code')->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->boolean('active')->default(true);
            $t->boolean('featured')->default(false);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('doctors', function (Blueprint $t) {
            $t->id();
            $t->string('simrs_code')->unique();
            $t->string('name');
            $t->foreignId('poli_id')->nullable()->constrained('polis')->nullOnDelete();
            $t->string('status')->default('Aktif'); // Aktif | Cuti | Nonaktif
            $t->json('schedule')->nullable();       // [[hari(0-6), "08.00", "12.00"], ...]
            $t->string('color')->default('#1F6FB2');
            $t->text('bio')->nullable();
            $t->string('photo')->nullable();
            $t->boolean('show')->default(true);
            $t->timestamps();
        });

        Schema::create('doctor_leaves', function (Blueprint $t) {
            $t->id();
            $t->string('simrs_code')->unique();
            $t->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $t->date('date_from');
            $t->date('date_to');
            $t->string('reason')->nullable();
            $t->timestamps();
        });

        Schema::create('appointments', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->foreignId('doctor_id')->constrained();
            $t->date('date');
            $t->string('time', 5);
            $t->string('name');
            $t->string('nik', 16)->nullable();
            $t->string('phone');
            $t->date('birth_date')->nullable();
            $t->string('payment');
            $t->string('bpjs_number')->nullable();
            $t->string('referral_number')->nullable();
            $t->string('medical_record')->nullable();
            $t->text('note')->nullable();
            $t->string('status')->default('Menunggu');
            $t->string('simrs_booking_code')->nullable();
            $t->string('queue_number')->nullable();
            $t->timestamp('simrs_sent_at')->nullable();
            $t->string('simrs_error')->nullable();
            $t->timestamps();
            $t->index(['doctor_id', 'date']);
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('user_name');
            $t->string('action');
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('sync_logs', function (Blueprint $t) {
            $t->id();
            $t->boolean('ok');
            $t->string('message');
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('chat_conversations', function (Blueprint $t) {
            $t->id();
            $t->string('token', 64)->unique();
            $t->string('name');
            $t->string('phone');
            $t->string('phone_norm', 20)->index();
            $t->string('topic')->nullable();
            $t->string('status')->default('menunggu'); // menunggu | aktif | selesai
            $t->unsignedInteger('sessions')->default(1);
            $t->timestamp('started_at')->nullable();
            $t->timestamp('last_at')->nullable()->index();
            $t->string('last_text')->nullable();
            $t->string('last_from')->nullable();
            $t->timestamp('agent_seen_at')->nullable();
            $t->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('note')->nullable();
            $t->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $t->string('sender'); // visitor | agent | system
            $t->text('body');
            $t->string('agent_label')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['chat_messages', 'chat_conversations', 'sync_logs', 'activity_logs', 'appointments', 'doctor_leaves', 'doctors', 'polis', 'services', 'slides', 'news', 'news_categories', 'menus', 'phones', 'settings'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropIndex(['role_id']);
            $t->dropColumn(['role_id', 'is_active', 'last_login_at']);
        });
        Schema::dropIfExists('roles');
    }
};
