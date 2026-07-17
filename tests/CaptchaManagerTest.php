<?php

namespace EduLazaro\Laracaptcha\Tests;

use EduLazaro\Laracaptcha\Drivers\RecaptchaV2Driver;
use EduLazaro\Laracaptcha\Drivers\RecaptchaV3Driver;
use EduLazaro\Laracaptcha\Drivers\TurnstileDriver;
use EduLazaro\Laracaptcha\Facades\Captcha;
use EduLazaro\Laracaptcha\Testing\FakeDriver;
use InvalidArgumentException;

class CaptchaManagerTest extends TestCase
{
    public function test_default_driver_comes_from_config(): void
    {
        $this->assertInstanceOf(TurnstileDriver::class, Captcha::driver());

        config()->set('laracaptcha.default', 'recaptcha_v2');
        app()->forgetInstance('laracaptcha');

        $this->assertSame('recaptcha_v2', app('laracaptcha')->driver()->name());
    }

    public function test_each_driver_resolves(): void
    {
        $this->assertInstanceOf(TurnstileDriver::class, Captcha::driver('turnstile'));
        $this->assertInstanceOf(RecaptchaV2Driver::class, Captcha::driver('recaptcha_v2'));
        $this->assertInstanceOf(RecaptchaV3Driver::class, Captcha::driver('recaptcha_v3'));
    }

    public function test_unknown_driver_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Captcha::driver('hcaptcha');
    }

    public function test_site_keys_and_response_fields(): void
    {
        $this->assertSame('turnstile-site-key', Captcha::driver('turnstile')->siteKey());
        $this->assertSame('cf-turnstile-response', Captcha::driver('turnstile')->responseField());
        $this->assertSame('g-recaptcha-response', Captcha::driver('recaptcha_v2')->responseField());
        $this->assertSame('g-recaptcha-response', Captcha::driver('recaptcha_v3')->responseField());
    }

    public function test_fake_replaces_every_driver(): void
    {
        Captcha::fake();

        $this->assertInstanceOf(FakeDriver::class, Captcha::driver());
        $this->assertInstanceOf(FakeDriver::class, Captcha::driver('turnstile'));
        $this->assertInstanceOf(FakeDriver::class, Captcha::driver('recaptcha_v2'));
        $this->assertInstanceOf(FakeDriver::class, Captcha::driver('recaptcha_v3'));
    }
}
