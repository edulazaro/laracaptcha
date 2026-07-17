<?php

namespace EduLazaro\Laracaptcha;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class LaracaptchaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laracaptcha.php', 'laracaptcha');

        $this->app->singleton('laracaptcha', fn ($app) => new CaptchaManager($app));
        $this->app->alias('laracaptcha', CaptchaManager::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laracaptcha');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'laracaptcha');

        // <x-laracaptcha::widget />
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'laracaptcha');

        $this->publishes([
            __DIR__.'/../config/laracaptcha.php' => config_path('laracaptcha.php'),
        ], 'laracaptcha-config');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/laracaptcha'),
        ], 'laracaptcha-lang');
    }
}
