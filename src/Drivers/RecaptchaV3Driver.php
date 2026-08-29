<?php

namespace EduLazaro\Laracaptcha\Drivers;

use EduLazaro\Laracaptcha\Contracts\ExpectsAction;
use EduLazaro\Laracaptcha\Support\VerificationResult;

/**
 * Google reCAPTCHA v3, the invisible one.
 *
 * Same credentials and same endpoint as v2, so only the judging differs: v3
 * returns a score from 0.0 (almost certainly a bot) to 1.0 and never blocks
 * anything by itself, which means the threshold is the whole point of the
 * driver. Config keys: `key`, `secret` and `min_score` (default 0.5).
 */
class RecaptchaV3Driver extends RecaptchaV2Driver implements ExpectsAction
{
    /** Action the token must have been minted for, null to accept any. */
    protected ?string $expectedAction = null;

    /**
     * A copy of this driver that only accepts tokens minted for $action.
     *
     * Returns a clone because the manager hands out the same driver instance
     * for the whole request: mutating it would leak one form's expected action
     * into every later verification.
     */
    public function expectingAction(?string $action): static
    {
        $clone = clone $this;
        $clone->expectedAction = $action;

        return $clone;
    }

    public function name(): string
    {
        return 'recaptcha_v3';
    }

    /**
     * Same siteverify endpoint as v2, but a v3 response has to clear two more
     * checks before it counts as a pass.
     *
     * First the action, when one is expected: Google stamps every token with
     * the action the widget asked for, and without comparing it a token minted
     * on a cheap form is good for an expensive one. A response with no action
     * at all is treated as a mismatch, so the check fails closed.
     *
     * Then the score: below the configured threshold the verification fails
     * even though Google reports success, because v3 never blocks anything by
     * itself and leaves the threshold to the application.
     */
    protected function toResult(array $data): VerificationResult
    {
        $result = parent::toResult($data);

        if (! $result->success) {
            return $result;
        }

        if ($this->expectedAction !== null && ($data['action'] ?? null) !== $this->expectedAction) {
            return new VerificationResult(
                success: false,
                score: $result->score,
                errorCodes: array_merge($result->errorCodes, ['action-mismatch']),
                raw: $data,
            );
        }

        $minScore = (float) ($this->config['min_score'] ?? 0.5);

        if ($result->score !== null && $result->score < $minScore) {
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
