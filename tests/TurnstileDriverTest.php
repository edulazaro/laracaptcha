<?php

namespace EduLazaro\Laracaptcha\Tests;

use EduLazaro\Laracaptcha\Facades\Captcha;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class TurnstileDriverTest extends TestCase
{
    public function test_successful_verification(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true]),
        ]);

        $result = Captcha::verify('the-token', '1.2.3.4');

        $this->assertTrue($result->passed());
        $this->assertSame([], $result->errorCodes);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'challenges.cloudflare.com/turnstile/v0/siteverify')
                && $request['secret'] === 'turnstile-secret'
                && $request['response'] === 'the-token'
                && $request['remoteip'] === '1.2.3.4';
        });
    }

    public function test_failed_verification_exposes_error_codes(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ]),
        ]);

        $result = Captcha::verify('bad-token');

        $this->assertTrue($result->failed());
        $this->assertSame(['invalid-input-response'], $result->errorCodes);
    }

    public function test_ip_is_omitted_when_not_provided(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true]),
        ]);

        Captcha::verify('the-token');

        Http::assertSent(fn (Request $request) => ! isset($request['remoteip']));
    }
}
