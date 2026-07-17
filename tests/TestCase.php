<?php

namespace EduLazaro\Laracaptcha\Tests;

use EduLazaro\Laracaptcha\Facades\Captcha;
use EduLazaro\Laracaptcha\LaracaptchaServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaracaptchaServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Captcha' => Captcha::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('laracaptcha.default', 'turnstile');
        $app['config']->set('laracaptcha.drivers.turnstile', [
            'key' => 'turnstile-site-key',
            'secret' => 'turnstile-secret',
        ]);
        $app['config']->set('laracaptcha.drivers.recaptcha_v2', [
            'key' => 'v2-site-key',
            'secret' => 'v2-secret',
        ]);
        $app['config']->set('laracaptcha.drivers.recaptcha_v3', [
            'key' => 'v3-site-key',
            'secret' => 'v3-secret',
            'min_score' => 0.5,
        ]);
    }
}
