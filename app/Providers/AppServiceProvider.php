<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Every website page gets the website settings, the farm's name and contacts, and the page menu.
        \Illuminate\Support\Facades\View::composer('website.*', function ($view) {
            $view->with([
                'site' => \App\Services\Website\WebsiteContent::settings(),
                'farm' => \App\Services\Website\WebsiteContent::farm(),
                'menu' => \App\Services\Website\WebsiteContent::menu(),
            ]);
        });
    }
}
