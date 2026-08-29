<?php

namespace EduLazaro\Laracaptcha;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the package into the application.
 *
 * Auto-discovered, so an install needs no manual registration. Publishes two
 * tags: `laracaptcha-config` and `laracaptcha-lang`. The views are loaded, not
 * published, which keeps the widget's markup in the package's hands.
 */
class LaracaptchaServiceProvider extends ServiceProvider
{
    /** Binds the manager as the "laracaptcha" singleton, which the facade resolves. */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laracaptcha.php', 'laracaptcha');

        $this->app->singleton('laracaptcha', fn ($app) => new CaptchaManager($app));
        $this->app->alias('laracaptcha', CaptchaManager::class);
    }

    /** Loads views, translations and the widget component, and declares what can be published. */
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
