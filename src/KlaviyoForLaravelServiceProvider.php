<?php

namespace DutchBridge\KlaviyoForLaravel;

use DutchBridge\KlaviyoForLaravel\Http\Controllers\KlaviyoWebhookController;
use DutchBridge\KlaviyoForLaravel\Http\Middleware\KlaviyoWebhookSecurity;
use DutchBridge\KlaviyoForLaravel\View\Creators\InitializeCreator;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class KlaviyoForLaravelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/klaviyo.php' => config_path('klaviyo.php'),
            ], ['klaviyo', 'klaviyo-config']);
        }

        View::creator('klaviyo::initialize', InitializeCreator::class);

        Event::subscribe(UserEventSubscriber::class);

        $this->registerWebhookRoutes();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/klaviyo.php', 'klaviyo'
        );

        $this->loadViewsFrom(
            __DIR__.'/../resources/views', 'klaviyo'
        );

        $this->app->singleton(KlaviyoClient::class, function () {
            return new KlaviyoClient(
                $this->app['config']->get('klaviyo', [])
            );
        });

        $this->app->alias(KlaviyoClient::class, 'klaviyo');

        $this->app->resolving(EncryptCookies::class, function (EncryptCookies $middleware) {
            $middleware->disableFor('__kla_id');
        });
    }

    /**
     * Register webhook routes.
     *
     * @return void
     */
    protected function registerWebhookRoutes(): void
    {
        if (!config('klaviyo.enabled', true)) {
            return;
        }

        Route::middleware(['api', KlaviyoWebhookSecurity::class])
            ->prefix('klaviyo')
            ->group(function () {
                Route::post('webhook', KlaviyoWebhookController::class)
                    ->name('klaviyo.webhook');
            });
    }
}
