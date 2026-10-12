<?php

namespace App\Providers;

use App\Admin\Modules;
use App\Models\Menu;
use App\Models\News;
use App\Services\Simrs\FakeSimrsClient;
use App\Services\Simrs\HttpSimrsClient;
use App\Services\Simrs\SimrsClient;
use App\Support\SiteSettings;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SimrsClient::class, fn () => config('serenapita.simrs.driver') === 'http'
            ? new HttpSimrsClient
            : new FakeSimrsClient);
    }

    public function boot(): void
    {
        Carbon::setLocale('id');
        Paginator::defaultView('partials.pagination');

        View::composer(['layouts.site', 'site.*'], function ($view) {
            $site = once(fn () => [
                'profile' => SiteSettings::profile(),
                'address' => SiteSettings::address(),
                'shortAddress' => SiteSettings::shortAddress(),
                'logo' => SiteSettings::logoUrl(),
                'logoDark' => SiteSettings::logoUrl(true),
                'menus' => Menu::where('visible', true)->orderBy('sort')->get(),
                'sections' => SiteSettings::get('sections'),
                'phones' => SiteSettings::phones(),
                'emergency' => SiteSettings::emergencyPhone(),
                'footerNews' => News::visible()->limit(3)->get(),
                'chatbot' => SiteSettings::get('chatbot'),
                'livechat' => SiteSettings::get('livechat'),
            ]);
            $view->with('site', $site);
        });

        View::composer('layouts.admin', function ($view) {
            $view->with('modules', Modules::GROUPS)->with('icons', Modules::ICONS)
                ->with('brand', ['profile' => SiteSettings::profile(), 'logoDark' => SiteSettings::logoUrl(true)]);
        });
    }
}
