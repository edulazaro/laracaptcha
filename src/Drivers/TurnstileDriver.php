<?php

namespace EduLazaro\Laracaptcha\Drivers;

use EduLazaro\Laracaptcha\Concerns\TalksToProvider;
use EduLazaro\Laracaptcha\Contracts\CaptchaDriver;
use EduLazaro\Laracaptcha\Support\VerificationResult;
/**
 * Cloudflare Turnstile.
 *
 * The simplest of the three: the siteverify endpoint answers with a plain
 * pass or fail and there is no score to weigh, so nothing here is tunable
 * beyond the credentials. Config keys: `key` (public, rendered into the
 * widget) and `secret` (server side).
 */
class TurnstileDriver implements CaptchaDriver
{
    use TalksToProvider;

    public function __construct(protected array $config)
    {
    }

    public function name(): string
    {
        return 'turnstile';
    }

    /**
     * Post the token to Cloudflare's siteverify endpoint.
     *
     * Fails closed in both directions: an error page decodes to an empty
     * payload and so carries no "success", and an unreachable Cloudflare comes
     * back as the "unreachable" error code instead of an exception. An outage
     * blocks submissions, it neither waves them through nor breaks the form.
     */
    public function verify(string $token, ?string $ip = null): VerificationResult
    {
        $data = $this->askProvider(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            $token,
            $ip,
        );

        if ($data === null) {
            return new VerificationResult(success: false, errorCodes: ['unreachable']);
        }

        return new VerificationResult(
            success: (bool) ($data['success'] ?? false),
            errorCodes: $data['error-codes'] ?? [],
            raw: $data,
        );
    }

    /** Empty string when unconfigured, which renders a widget Cloudflare rejects. */
    public function siteKey(): string
    {
        return $this->config['key'] ?? '';
    }

    public function scriptUrl(): string
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    }

    public function responseField(): string
    {
        return 'cf-turnstile-response';
    }
}
