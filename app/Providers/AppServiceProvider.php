<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PayPalService;
use OpenAI\Client as OpenAIClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register PayPalService as a singleton
        $this->app->singleton(PayPalService::class, function ($app) {
            return new PayPalService();
        });

        // Register OpenAI Client as a singleton
        $this->app->singleton(OpenAIClient::class, function () {
            return OpenAIClient::factory()
                ->setApiKey(env('OPENAI_API_KEY'))
                ->make();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
