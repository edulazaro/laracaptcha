<?php

namespace EduLazaro\Laracaptcha\Tests;

use EduLazaro\Laracaptcha\Facades\Captcha;
use EduLazaro\Laracaptcha\Rules\Captcha as CaptchaRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

/**
 * The provider does not answer at all: refused connection, DNS failure, or a
 * request that ran past the timeout.
 *
 * This has to be a failed verification and nothing louder. Letting the
 * connection error out would turn an outage at Cloudflare or Google into a 500
 * on every form the widget sits on, which is a worse outage than the one that
 * caused it.
 */
class UnreachableProviderTest extends TestCase
{
    private function providerIsDown(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));
    }

    public function test_turnstile_reports_a_failure_instead_of_throwing(): void
    {
        $this->providerIsDown();

        $result = Captcha::driver('turnstile')->verify('the-token');

        $this->assertTrue($result->failed());
        $this->assertContains('unreachable', $result->errorCodes);
    }

    public function test_recaptcha_reports_a_failure_instead_of_throwing(): void
    {
        $this->providerIsDown();

        $result = Captcha::driver('recaptcha_v2')->verify('the-token');

        $this->assertTrue($result->failed());
        $this->assertContains('unreachable', $result->errorCodes);
    }

    public function test_recaptcha_v3_reports_a_failure_instead_of_throwing(): void
    {
        $this->providerIsDown();

        $result = Captcha::driver('recaptcha_v3')->verify('the-token');

        $this->assertTrue($result->failed());
        $this->assertContains('unreachable', $result->errorCodes);
    }

    public function test_the_rule_fails_the_field_instead_of_breaking_the_request(): void
    {
        $this->providerIsDown();

        $validator = Validator::make(
            ['cf-turnstile-response' => 'the-token'],
            ['cf-turnstile-response' => ['required', new CaptchaRule]]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_an_outage_does_not_burn_the_token(): void
    {
        // One stub for both halves: Http::fake() merges rather than replaces,
        // so a second call would not retire the first.
        $down = true;

        Http::fake(function () use (&$down) {
            if ($down) {
                throw new ConnectionException('cURL error 28: Operation timed out');
            }

            return Http::response(['success' => true]);
        });

        $data = ['cf-turnstile-response' => 'the-token'];
        $rules = ['cf-turnstile-response' => ['required', new CaptchaRule]];

        $this->assertTrue(Validator::make($data, $rules)->fails());

        // Replay protection only remembers tokens that actually passed, so the
        // same token is still good once the provider is back.
        $this->assertFalse(Cache::has('laracaptcha|'.md5('the-token')));

        $down = false;

        $this->assertTrue(Validator::make($data, $rules)->passes());
    }

    public function test_an_error_page_is_still_a_failure_and_not_an_outage(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response('<html>502</html>', 502)]);

        $result = Captcha::driver('turnstile')->verify('the-token');

        $this->assertTrue($result->failed());
        $this->assertNotContains('unreachable', $result->errorCodes);
    }
}
