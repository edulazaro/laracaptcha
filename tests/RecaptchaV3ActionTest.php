<?php

namespace EduLazaro\Laracaptcha\Tests;

use EduLazaro\Laracaptcha\Facades\Captcha;
use EduLazaro\Laracaptcha\Rules\Captcha as CaptchaRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class RecaptchaV3ActionTest extends TestCase
{
    private function googleReturns(array $payload): void
    {
        Http::fake(['www.google.com/*' => Http::response($payload)]);
    }

    public function test_a_token_minted_for_the_expected_action_passes(): void
    {
        $this->googleReturns(['success' => true, 'score' => 0.9, 'action' => 'login']);

        $result = Captcha::driver('recaptcha_v3')->expectingAction('login')->verify('the-token');

        $this->assertTrue($result->passed());
    }

    public function test_a_token_minted_for_another_action_is_refused(): void
    {
        $this->googleReturns(['success' => true, 'score' => 0.9, 'action' => 'contact']);

        $result = Captcha::driver('recaptcha_v3')->expectingAction('payment')->verify('the-token');

        $this->assertTrue($result->failed());
        $this->assertContains('action-mismatch', $result->errorCodes);
    }

    public function test_a_response_without_an_action_is_refused(): void
    {
        $this->googleReturns(['success' => true, 'score' => 0.9]);

        $result = Captcha::driver('recaptcha_v3')->expectingAction('login')->verify('the-token');

        $this->assertTrue($result->failed());
        $this->assertContains('action-mismatch', $result->errorCodes);
    }

    public function test_nothing_is_checked_when_no_action_is_expected(): void
    {
        $this->googleReturns(['success' => true, 'score' => 0.9, 'action' => 'whatever']);

        $this->assertTrue(Captcha::driver('recaptcha_v3')->verify('the-token')->passed());
    }

    public function test_expecting_an_action_does_not_touch_the_cached_driver(): void
    {
        $this->googleReturns(['success' => true, 'score' => 0.9, 'action' => 'contact']);

        $driver = Captcha::driver('recaptcha_v3');
        $driver->expectingAction('payment');

        // The original must be unchanged, or one form's action would leak
        // into every later verification of the same request.
        $this->assertTrue($driver->verify('the-token')->passed());
    }

    public function test_the_score_still_wins_when_the_action_matches(): void
    {
        $this->googleReturns(['success' => true, 'score' => 0.1, 'action' => 'login']);

        $result = Captcha::driver('recaptcha_v3')->expectingAction('login')->verify('the-token');

        $this->assertTrue($result->failed());
        $this->assertContains('low-score', $result->errorCodes);
    }

    public function test_the_rule_forwards_the_action(): void
    {
        $this->googleReturns(['success' => true, 'score' => 0.9, 'action' => 'contact']);

        $validator = Validator::make(
            ['g-recaptcha-response' => 'the-token'],
            ['g-recaptcha-response' => ['required', CaptchaRule::make('recaptcha_v3', 'login')]]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_the_rule_passes_when_the_action_matches(): void
    {
        $this->googleReturns(['success' => true, 'score' => 0.9, 'action' => 'login']);

        $validator = Validator::make(
            ['g-recaptcha-response' => 'the-token'],
            ['g-recaptcha-response' => ['required', CaptchaRule::make('recaptcha_v3', 'login')]]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_an_action_on_a_driver_that_has_none_is_ignored(): void
    {
        Http::fake(['www.google.com/*' => Http::response(['success' => true])]);

        $validator = Validator::make(
            ['g-recaptcha-response' => 'the-token'],
            ['g-recaptcha-response' => ['required', CaptchaRule::make('recaptcha_v2', 'login')]]
        );

        $this->assertTrue($validator->passes());
    }
}
