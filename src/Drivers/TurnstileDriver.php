<?php

namespace EduLazaro\Laracaptcha\Drivers;

use EduLazaro\Laracaptcha\Contracts\CaptchaDriver;
use EduLazaro\Laracaptcha\Support\VerificationResult;
use Illuminate\Support\Facades\Http;

class TurnstileDriver implements CaptchaDriver
{
    public function __construct(protected array $config)
    {
    }

    public function name(): string
    {
        return 'turnstile';
    }

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
