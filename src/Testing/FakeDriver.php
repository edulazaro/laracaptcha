<?php

namespace EduLazaro\Laracaptcha\Testing;

use EduLazaro\Laracaptcha\Contracts\CaptchaDriver;
use EduLazaro\Laracaptcha\Support\VerificationResult;

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

    /** @return array<int, array{token: string, ip: string|null}> */
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
