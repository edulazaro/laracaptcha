<?php

namespace EduLazaro\Laracaptcha\Support;

/**
 * The outcome of verifying one captcha token.
 *
 * Immutable and provider agnostic: every driver flattens its own response
 * shape into these four fields, and the raw payload is kept in `raw` for
 * whoever needs a provider specific detail. Note that `success` is the
 * package's verdict, not the provider's: reCAPTCHA v3 reports success on a
 * token that scores below the configured threshold, and the driver turns that
 * into a failure with a "low-score" error code.
 */
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

    /** The token is good and the request may go through. */
    public function passed(): bool
    {
        return $this->success;
    }

    /** The token was refused, scored too low, or could not be checked at all. */
    public function failed(): bool
    {
        return ! $this->success;
    }
}
