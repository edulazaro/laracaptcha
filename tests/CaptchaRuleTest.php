<?php

namespace EduLazaro\Laracaptcha\Tests;

use EduLazaro\Laracaptcha\Rules\Captcha as CaptchaRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CaptchaRuleTest extends TestCase
{
    /**
     * Collect the rule's own log lines while $run executes.
     *
     * Only the rule's: the listener hears every channel, and anything else the
     * framework writes while the test runs would be counted as ours. A PHP nightly
     * logging one deprecation is enough to do it, which is how CI found this.
     *
     * @param callable $run
     * @return array<int, array<string, mixed>>
     */
    private function captchaLog(callable $run): array
    {
        $lines = [];

        Log::listen(function ($message) use (&$lines) {
            if ($message->message === 'Captcha verification failed') {
                $lines[] = $message->context;
            }
        });

        $run();

        return $lines;
    }

    /**
     * Run the rule against a refusal carrying $errorCodes and hand back the log lines
     * it left behind.
     *
     * @param array<int, string> $errorCodes
     * @return array<int, array<string, mixed>>
     */
    private function logged(array $errorCodes, array $payload = []): array
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(array_merge(
                ['success' => false, 'error-codes' => $errorCodes],
                $payload,
            )),
        ]);

        return $this->captchaLog(fn () => Validator::make(
            ['cf-turnstile-response' => 'the-token'],
            ['cf-turnstile-response' => ['required', new CaptchaRule]]
        )->fails());
    }

    public function test_a_refusal_leaves_the_providers_own_reason_in_the_log(): void
    {
        $lines = $this->logged(['invalid-input-secret'], ['hostname' => 'example.com']);

        $this->assertCount(1, $lines);
        $this->assertSame('turnstile', $lines[0]['driver']);
        $this->assertSame(['invalid-input-secret'], $lines[0]['errors']);
        $this->assertSame('example.com', $lines[0]['hostname']);
    }

    public function test_a_refusal_with_no_codes_still_says_so(): void
    {
        $lines = $this->logged([]);

        $this->assertSame(['none-reported'], $lines[0]['errors']);
    }

    public function test_logging_can_be_turned_off(): void
    {
        config(['laracaptcha.log_failures' => false]);

        $this->assertSame([], $this->logged(['invalid-input-response']));
    }

    public function test_an_empty_field_is_not_worth_a_log_line(): void
    {
        $lines = $this->captchaLog(fn () => Validator::make(
            ['cf-turnstile-response' => ''],
            ['cf-turnstile-response' => ['required', new CaptchaRule]]
        )->fails());

        $this->assertSame([], $lines);
    }

    public function test_a_replayed_token_is_logged_as_such(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $data = ['cf-turnstile-response' => 'the-token'];
        $rules = ['cf-turnstile-response' => ['required', new CaptchaRule]];

        $this->assertTrue(Validator::make($data, $rules)->passes());

        $lines = $this->captchaLog(fn () => $this->assertTrue(Validator::make($data, $rules)->fails()));

        $this->assertCount(1, $lines);
        $this->assertSame(['already-used'], $lines[0]['errors']);
    }

    public function test_valid_token_passes(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true]),
        ]);

        $validator = Validator::make(
            ['cf-turnstile-response' => 'valid-token'],
            ['cf-turnstile-response' => ['required', new CaptchaRule]]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_invalid_token_fails(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => false]),
        ]);

        $validator = Validator::make(
            ['cf-turnstile-response' => 'bad-token'],
            ['cf-turnstile-response' => ['required', new CaptchaRule]]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_token_cannot_be_replayed(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true]),
        ]);

        $data = ['cf-turnstile-response' => 'replayed-token'];
        $rules = ['cf-turnstile-response' => ['required', new CaptchaRule]];

        $this->assertTrue(Validator::make($data, $rules)->passes());
        $this->assertTrue(Validator::make($data, $rules)->fails());

        // The provider is only queried the first time.
        Http::assertSentCount(1);
    }

    public function test_replay_protection_can_be_disabled(): void
    {
        config()->set('laracaptcha.prevent_reuse', false);

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true]),
        ]);

        $data = ['cf-turnstile-response' => 'reusable-token'];
        $rules = ['cf-turnstile-response' => ['required', new CaptchaRule]];

        $this->assertTrue(Validator::make($data, $rules)->passes());
        $this->assertTrue(Validator::make($data, $rules)->passes());
    }

    public function test_rule_can_target_a_specific_driver(): void
    {
        Http::fake([
            'www.google.com/*' => Http::response(['success' => true]),
        ]);

        $validator = Validator::make(
            ['g-recaptcha-response' => 'valid-token'],
            ['g-recaptcha-response' => ['required', new CaptchaRule('recaptcha_v2')]]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_fake_makes_the_rule_pass_without_http(): void
    {
        Http::fake();

        \EduLazaro\Laracaptcha\Facades\Captcha::fake();

        $validator = Validator::make(
            ['cf-turnstile-response' => 'anything'],
            ['cf-turnstile-response' => ['required', new CaptchaRule]]
        );

        $this->assertTrue($validator->passes());
        Http::assertNothingSent();
    }
}
