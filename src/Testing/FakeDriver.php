<?php

namespace EduLazaro\Laracaptcha\Testing;

use EduLazaro\Laracaptcha\Contracts\CaptchaDriver;
use EduLazaro\Laracaptcha\Support\VerificationResult;

/**
 * Driver that answers without talking to anyone, for tests.
 *
 * Install it with `Captcha::fake()`, which points every configured driver name
 * at one instance, then assert on what reached it:
 *
 *   $fake = Captcha::fake();                  // every token passes
 *   $fake = Captcha::fake(success: false);    // every token is refused
 *   $this->assertCount(1, $fake->attempts());
 *
 * `scriptUrl()` is deliberately empty so the widget renders no external script
 * in a test run.
 */
class FakeDriver implements CaptchaDriver
{
    /** @var array<int, array{token: string, ip: string|null}> */
    public array $attempts = [];

    public function __construct(
        protected bool $success = true,
        protected ?float $score = null,
    ) {
    }

    public function name(): string
    {
        return 'fake';
    }

    /** Records the attempt, then answers with whatever the fake was built with. */
    public function verify(string $token, ?string $ip = null): VerificationResult
    {
        $this->attempts[] = ['token' => $token, 'ip' => $ip];

        return new VerificationResult(
            success: $this->success,
            score: $this->score,
            errorCodes: $this->success ? [] : ['fake-failure'],
            raw: ['fake' => true],
        );
    }

    /**
     * Every token this fake was asked about, oldest first.
     *
     * @return array<int, array{token: string, ip: string|null}>
     */
    public function attempts(): array
    {
        return $this->attempts;
    }

    public function siteKey(): string
    {
        return 'fake-site-key';
    }

    public function scriptUrl(): string
    {
        return '';
    }

    public function responseField(): string
    {
        return 'captcha-response';
    }
}
