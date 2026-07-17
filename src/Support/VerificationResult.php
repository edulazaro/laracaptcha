<?php

namespace EduLazaro\Laracaptcha\Support;

class VerificationResult
{
    /**
     * @param bool $success Whether the token passed verification (including score checks).
     * @param float|null $score Score reported by the provider (reCAPTCHA v3), null otherwise.
     * @param array<int, string> $errorCodes Provider error codes, if any.
     * @param array<string, mixed> $raw Raw provider response.
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?float $score = null,
        public readonly array $errorCodes = [],
        public readonly array $raw = [],
    ) {
    }

    public function passed(): bool
    {
        return $this->success;
    }

    public function failed(): bool
    {
        return ! $this->success;
    }
}
