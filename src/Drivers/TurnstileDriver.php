<?php

namespace EduLazaro\Laracaptcha\Drivers;

use EduLazaro\Laracaptcha\Concerns\TalksToProvider;
use EduLazaro\Laracaptcha\Contracts\CaptchaDriver;
use EduLazaro\Laracaptcha\Contracts\ExpectsAction;
use EduLazaro\Laracaptcha\Support\VerificationResult;

/**
 * Cloudflare Turnstile.
 *
 * The siteverify endpoint answers with a plain pass or fail and there is no
 * score to weigh. What it does report is the action the widget was rendered
 * with and the hostname it ran on, and both can be held to what the form
 * expects. Config keys: `key` (public, rendered into the widget) and `secret`
 * (server side).
 */
class TurnstileDriver implements CaptchaDriver, ExpectsAction
{
    use TalksToProvider;

    /** Action the token must have been minted for, null to accept any. */
    protected ?string $expectedAction = null;

    public function __construct(protected array $config)
    {
    }

    public function name(): string
    {
        return 'turnstile';
    }

    /**
     * A copy of this driver that only accepts tokens minted for $action.
     *
     * The widget takes the same name (`<x-laracaptcha::widget action="login" />`),
     * which Cloudflare signs into the token. A clone, because the manager hands
     * out one instance for the whole request.
     */
    public function expectingAction(?string $action): static
    {
        $clone = clone $this;
        $clone->expectedAction = $action;

        return $clone;
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

        $result = new VerificationResult(
            success: (bool) ($data['success'] ?? false),
            errorCodes: $data['error-codes'] ?? [],
            raw: $data,
        );

        if ($result->success && $this->expectedAction !== null && ($data['action'] ?? null) !== $this->expectedAction) {
            return new VerificationResult(
                success: false,
                errorCodes: array_merge($result->errorCodes, ['action-mismatch']),
                raw: $data,
            );
        }

        return $this->checkHostname($result, $data);
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
