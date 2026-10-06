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

    public function test_a_token_minted_for_the_expected_action_passes(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true, 'action' => 'login'])]);

        $this->assertTrue(Captcha::driver('turnstile')->expectingAction('login')->verify('t')->passed());
    }

    public function test_a_token_minted_for_another_action_is_refused(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true, 'action' => 'contact'])]);

        $result = Captcha::driver('turnstile')->expectingAction('login')->verify('t');

        $this->assertTrue($result->failed());
        $this->assertContains('action-mismatch', $result->errorCodes);
    }

    public function test_expecting_an_action_does_not_change_the_shared_driver(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true, 'action' => 'contact'])]);

        Captcha::driver('turnstile')->expectingAction('login');

        $this->assertTrue(Captcha::driver('turnstile')->verify('t')->passed());
    }

    public function test_with_hostnames_configured_another_site_is_refused(): void
    {
        config(['laracaptcha.hostnames' => ['example.com']]);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true, 'hostname' => 'evil.test'])]);

        $result = Captcha::verify('t');

        $this->assertTrue($result->failed());
        $this->assertContains('hostname-mismatch', $result->errorCodes);
    }

    public function test_with_hostnames_configured_the_listed_site_passes_whatever_its_case(): void
    {
        config(['laracaptcha.hostnames' => ['Example.com']]);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true, 'hostname' => 'example.COM'])]);

        $this->assertTrue(Captcha::verify('t')->passed());
    }

    public function test_with_hostnames_configured_a_response_naming_none_is_refused(): void
    {
        config(['laracaptcha.hostnames' => ['example.com']]);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $this->assertTrue(Captcha::verify('t')->failed());
    }

    public function test_without_hostnames_nothing_is_compared(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true, 'hostname' => 'anything.test'])]);

        $this->assertTrue(Captcha::verify('t')->passed());
    }
}
