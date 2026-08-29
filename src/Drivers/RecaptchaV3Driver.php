<?php

namespace EduLazaro\Laracaptcha\Drivers;

use EduLazaro\Laracaptcha\Support\VerificationResult;

/**
 * Google reCAPTCHA v3, the invisible one.
 *
 * Same credentials and same endpoint as v2, so only the judging differs: v3
 * returns a score from 0.0 (almost certainly a bot) to 1.0 and never blocks
 * anything by itself, which means the threshold is the whole point of the
 * driver. Config keys: `key`, `secret` and `min_score` (default 0.5).
 */
class RecaptchaV3Driver extends RecaptchaV2Driver
{
    public function name(): string
    {
        return 'recaptcha_v3';
    }

    /**
     * Same siteverify endpoint as v2, but v3 responses carry a score:
     * below the configured threshold the verification fails even if
     * Google reports success.
     */
    protected function toResult(array $data): VerificationResult
    {
        $result = parent::toResult($data);

        $minScore = (float) ($this->config['min_score'] ?? 0.5);

        if ($result->success && $result->score !== null && $result->score < $minScore) {
            return new VerificationResult(
                success: false,
                score: $result->score,
                errorCodes: array_merge($result->errorCodes, ['low-score']),
                raw: $data,
            );
        }

        return $result;
    }
}
