<?php

use App\Admin\Resources;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Site;
use App\Http\Controllers\SimrsWebhookController;
use Illuminate\Support\Facades\Route;

/* ---------------- Public website ---------------- */
Route::get('/', Site\HomeController::class)->name('home');
Route::get('/sitemap.xml', Site\SitemapController::class)->name('sitemap');
Route::get('/news', [Site\NewsController::class, 'index'])->middleware('page:news')->name('news.index');
Route::get('/news/{slug}', [Site\NewsController::class, 'show'])->name('news.show');
Route::get('/doctors', [Site\DoctorController::class, 'index'])->middleware('page:doctors')->name('doctors');
Route::get('/schedule', [Site\DoctorController::class, 'schedule'])->middleware('page:schedule')->name('schedule');
Route::get('/services', [Site\PageController::class, 'services'])->middleware('page:services')->name('services');
Route::get('/contact', [Site\PageController::class, 'contact'])->middleware('page:contact')->name('contact');
Route::get('/appointments', [Site\AppointmentController::class, 'create'])->middleware('page:appointment')->name('appointment');
Route::get('/appointments/times', [Site\AppointmentController::class, 'times'])->middleware('page:appointment')->name('appointment.times');
Route::post('/appointments', [Site\AppointmentController::class, 'store'])->middleware(['throttle:10,1', 'page:appointment'])->name('appointment.store');

Route::get('/careers', [Site\CareerController::class, 'index'])->name('careers.index');
Route::get('/careers/{slug}', [Site\CareerController::class, 'show'])->name('careers.show');
Route::post('/careers/{slug}/apply', [Site\CareerController::class, 'apply'])->middleware('throttle:5,1')->name('careers.apply');

Route::post('/chatbot', Site\ChatbotController::class)->middleware('throttle:20,1')->name('chatbot');
Route::prefix('livechat')->name('livechat.')->group(function () {
    Route::post('/start', [Site\LiveChatController::class, 'start'])->middleware('throttle:6,1')->name('start');
    Route::get('/poll', [Site\LiveChatController::class, 'poll'])->middleware('throttle:60,1')->name('poll');
    Route::post('/send', [Site\LiveChatController::class, 'send'])->middleware('throttle:30,1')->name('send');
    Route::post('/end', [Site\LiveChatController::class, 'end'])->name('end');
});

Route::post('/api/simrs/webhook', SimrsWebhookController::class)->middleware('throttle:60,1')->name('simrs.webhook');

/* ---------------- Backoffice ---------------- */
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [Admin\AuthController::class, 'show'])->name('login');
    Route::post('/login', [Admin\AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');

    Route::middleware(['auth', 'staff'])->group(function () {
        Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');
        Route::get('/', [Admin\AuthController::class, 'home'])->name('home');

        Route::get('/help', Admin\HelpController::class)->name('help');
        Route::get('/dashboard', Admin\DashboardController::class)->middleware('module:dashboard')->name('dashboard');

        Route::middleware('module:livechat')->prefix('livechat')->name('livechat.')->group(function () {
            Route::get('/', [Admin\LiveChatController::class, 'index'])->name('index');
            Route::post('/settings', [Admin\LiveChatController::class, 'saveSettings'])->name('settings');
            Route::get('/feed', [Admin\LiveChatController::class, 'feed'])->name('feed');
            Route::get('/count', [Admin\LiveChatController::class, 'count'])->name('count');
            Route::get('/{conversation}', [Admin\LiveChatController::class, 'show'])->name('show');
            Route::post('/{conversation}/send', [Admin\LiveChatController::class, 'send'])->name('send');
            Route::post('/{conversation}/status', [Admin\LiveChatController::class, 'status'])->name('status');
            Route::post('/{conversation}/note', [Admin\LiveChatController::class, 'note'])->name('note');
            Route::post('/{conversation}/unblock', [Admin\LiveChatController::class, 'unblock'])->name('unblock');
        });

        Route::get('/schedule', Admin\ScheduleController::class)->middleware('module:schedule')->name('schedule');

        Route::middleware('module:info')->group(function () {
            Route::get('/info', [Admin\ContentController::class, 'info'])->name('info');
            Route::post('/info', [Admin\ContentController::class, 'saveInfo']);
        });
        Route::middleware('module:menu')->group(function () {
            Route::get('/menu', [Admin\ContentController::class, 'menu'])->name('menu');
            Route::post('/menu', [Admin\ContentController::class, 'saveMenu']);
        });
        Route::middleware('module:profile')->group(function () {
            Route::get('/profile', [Admin\ContentController::class, 'profile'])->name('profile');
            Route::post('/profile', [Admin\ContentController::class, 'saveProfile']);
        });
        Route::middleware('module:appointment-form')->group(function () {
            Route::get('/appointment-form', [Admin\ContentController::class, 'booking'])->name('booking');
            Route::post('/appointment-form', [Admin\ContentController::class, 'saveBooking']);
        });
        Route::middleware('module:chatbot')->group(function () {
            Route::get('/chatbot', [Admin\ContentController::class, 'chatbot'])->name('chatbot');
            Route::post('/chatbot', [Admin\ContentController::class, 'saveChatbot']);
            Route::post('/chatbot/test', [Admin\ContentController::class, 'testChatbot'])->middleware('throttle:20,1')->name('chatbot.test');
        });
        Route::middleware('module:simrs')->group(function () {
            Route::get('/simrs', [Admin\SimrsController::class, 'index'])->name('simrs');
            Route::post('/simrs', [Admin\SimrsController::class, 'save']);
            Route::post('/simrs/test', [Admin\SimrsController::class, 'test'])->name('simrs.test');
            Route::post('/simrs/sync', [Admin\SimrsController::class, 'sync'])->name('simrs.sync');
            Route::post('/simrs/source', [Admin\SimrsController::class, 'source'])->name('simrs.source');
        });
        Route::middleware('module:roles')->group(function () {
            Route::get('/roles', [Admin\RoleController::class, 'index'])->name('roles');
            Route::put('/roles', [Admin\RoleController::class, 'save'])->name('roles.update');
            Route::post('/roles', [Admin\RoleController::class, 'store'])->name('roles.store');
            Route::delete('/roles/{role}', [Admin\RoleController::class, 'destroy'])->name('roles.destroy');
        });
        Route::get('/logs', Admin\LogController::class)->middleware('module:logs')->name('logs');
        Route::get('/applications/{application}/file/{type}', Admin\ApplicationFileController::class)->where('type', 'cv|doc')->middleware('module:applications')->name('applications.file');
        Route::post('/appointments/{appointment}/resend', [Admin\AppointmentController::class, 'resend'])->middleware('module:appointments')->name('appointments.resend');

        // Generic CRUD: news, categories, slides, services, doctors, clinics, appointments, users
        $crud = implode('|', array_keys(Resources::all()));
        Route::middleware('module')->group(function () use ($crud) {
            Route::get('/{resource}', [Admin\CrudController::class, 'index'])->where('resource', $crud)->name('crud.index');
            Route::get('/{resource}/create', [Admin\CrudController::class, 'create'])->where('resource', $crud)->name('crud.create');
            Route::post('/{resource}', [Admin\CrudController::class, 'store'])->where('resource', $crud)->name('crud.store');
            Route::get('/{resource}/{id}/edit', [Admin\CrudController::class, 'edit'])->where('resource', $crud)->name('crud.edit');
            Route::put('/{resource}/{id}', [Admin\CrudController::class, 'update'])->where('resource', $crud)->name('crud.update');
            Route::delete('/{resource}/{id}', [Admin\CrudController::class, 'destroy'])->where('resource', $crud)->name('crud.destroy');
            Route::post('/{resource}/{id}/toggle/{field}', [Admin\CrudController::class, 'toggle'])->where('resource', $crud)->name('crud.toggle');
            Route::post('/{resource}/{id}/move/{dir}', [Admin\CrudController::class, 'move'])->where(['resource' => $crud, 'dir' => 'up|down'])->name('crud.move');
        });
    });
});
