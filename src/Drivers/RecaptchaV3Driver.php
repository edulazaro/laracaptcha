<?php

namespace EduLazaro\Laracaptcha\Drivers;

use EduLazaro\Laracaptcha\Support\VerificationResult;

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
