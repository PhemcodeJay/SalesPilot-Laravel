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
        //
    }
}
// app/Providers/AppServiceProvider.php

use App\Services\PayPalService;

public function register()
{
    $this->app->singleton(PayPalService::class, function ($app) {
        return new PayPalService();
    });
}
