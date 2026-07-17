<?php

namespace EduLazaro\Laracaptcha\Tests;

use EduLazaro\Laracaptcha\Rules\Captcha as CaptchaRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class CaptchaRuleTest extends TestCase
{
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
