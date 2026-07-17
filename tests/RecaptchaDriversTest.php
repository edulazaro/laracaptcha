<?php

namespace EduLazaro\Laracaptcha\Tests;

use EduLazaro\Laracaptcha\Facades\Captcha;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class RecaptchaDriversTest extends TestCase
{
    public function test_v2_successful_verification(): void
    {
        Http::fake([
            'www.google.com/*' => Http::response(['success' => true]),
        ]);

        $result = Captcha::driver('recaptcha_v2')->verify('the-token', '1.2.3.4');

        $this->assertTrue($result->passed());
        $this->assertNull($result->score);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'www.google.com/recaptcha/api/siteverify')
                && $request['secret'] === 'v2-secret'
                && $request['response'] === 'the-token';
        });
    }

    public function test_v3_passes_when_score_meets_threshold(): void
    {
        Http::fake([
            'www.google.com/*' => Http::response(['success' => true, 'score' => 0.9]),
        ]);

        $result = Captcha::driver('recaptcha_v3')->verify('the-token');

        $this->assertTrue($result->passed());
        $this->assertSame(0.9, $result->score);
    }

    public function test_v3_fails_below_min_score_even_if_google_says_success(): void
    {
        Http::fake([
            'www.google.com/*' => Http::response(['success' => true, 'score' => 0.2]),
        ]);

        $result = Captcha::driver('recaptcha_v3')->verify('the-token');

        $this->assertTrue($result->failed());
        $this->assertSame(0.2, $result->score);
        $this->assertContains('low-score', $result->errorCodes);
    }

    public function test_v3_min_score_is_configurable(): void
    {
        config()->set('laracaptcha.drivers.recaptcha_v3.min_score', 0.1);
        app()->forgetInstance('laracaptcha');

        Http::fake([
            'www.google.com/*' => Http::response(['success' => true, 'score' => 0.2]),
        ]);

        $result = app('laracaptcha')->driver('recaptcha_v3')->verify('the-token');

        $this->assertTrue($result->passed());
    }
}
