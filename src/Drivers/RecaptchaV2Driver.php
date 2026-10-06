<?php

namespace EduLazaro\Laracaptcha\Drivers;

use EduLazaro\Laracaptcha\Concerns\TalksToProvider;
use EduLazaro\Laracaptcha\Contracts\CaptchaDriver;
use EduLazaro\Laracaptcha\Support\VerificationResult;
/**
 * Google reCAPTCHA v2, the "I am not a robot" checkbox.
 *
 * Pass or fail, with no score involved. Config keys: `key` and `secret`.
 * RecaptchaV3Driver extends this class because v3 talks to the same endpoint
 * and only differs in how the response is judged.
 */
class RecaptchaV2Driver implements CaptchaDriver
{
    use TalksToProvider;

    public function __construct(protected array $config)
    {
    }

    public function name(): string
    {
        return 'recaptcha_v2';
    }

    /**
     * Post the token to Google's siteverify endpoint.
     *
     * Fails closed, like every driver: a non-JSON reply and an unreachable
     * Google both end up as an unsuccessful result rather than an exception.
     * Subclasses do not override this, they override toResult() to judge the
     * payload differently.
     */
    public function verify(string $token, ?string $ip = null): VerificationResult
    {
        $data = $this->askProvider(
            'https://www.google.com/recaptcha/api/siteverify',
            $token,
            $ip,
        );

        if ($data === null) {
            return new VerificationResult(success: false, errorCodes: ['unreachable']);
        }

        return $this->toResult($data);
    }

    /**
     * Turn Google's payload into a VerificationResult.
     *
     * The seam v3 hooks into. At this level the provider's own "success" flag
     * is taken at face value; the score is carried over when present but is
     * not weighed. The hostname is, when `laracaptcha.hostnames` lists any.
     */
    protected function toResult(array $data): VerificationResult
    {
        return $this->checkHostname(new VerificationResult(
            success: (bool) ($data['success'] ?? false),
            score: isset($data['score']) ? (float) $data['score'] : null,
            errorCodes: $data['error-codes'] ?? [],
            raw: $data,
        ), $data);
    }

    /** Empty string when unconfigured, which renders a widget Google rejects. */
    public function siteKey(): string
    {
        return $this->config['key'] ?? '';
    }

    public function scriptUrl(): string
    {
        return 'https://www.google.com/recaptcha/api.js';
    }

    public function responseField(): string
    {
        return 'g-recaptcha-response';
    }
}
