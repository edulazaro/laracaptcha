<?php

namespace EduLazaro\Laracaptcha\Drivers;

use EduLazaro\Laracaptcha\Contracts\CaptchaDriver;
use EduLazaro\Laracaptcha\Support\VerificationResult;
use Illuminate\Support\Facades\Http;

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
     * Fails closed: `->json()` returns null when the response is not JSON,
     * which the `?? []` turns into a missing "success" key and therefore an
     * unsuccessful result. A Cloudflare outage blocks submissions, it does not
     * wave them through.
     */
    public function verify(string $token, ?string $ip = null): VerificationResult
    {
        $data = Http::asForm()
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array_filter([
                'secret' => $this->config['secret'] ?? '',
                'response' => $token,
                'remoteip' => $ip,
            ]))
            ->json() ?? [];

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
