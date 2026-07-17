<?php

namespace EduLazaro\Laracaptcha\Drivers;

use EduLazaro\Laracaptcha\Contracts\CaptchaDriver;
use EduLazaro\Laracaptcha\Support\VerificationResult;
use Illuminate\Support\Facades\Http;

class RecaptchaV2Driver implements CaptchaDriver
{
    public function __construct(protected array $config)
    {
    }

    public function name(): string
    {
        return 'recaptcha_v2';
    }

    public function verify(string $token, ?string $ip = null): VerificationResult
    {
        $data = Http::asForm()
            ->post('https://www.google.com/recaptcha/api/siteverify', array_filter([
                'secret' => $this->config['secret'] ?? '',
                'response' => $token,
                'remoteip' => $ip,
            ]))
            ->json() ?? [];

        return $this->toResult($data);
    }

    protected function toResult(array $data): VerificationResult
    {
        return new VerificationResult(
            success: (bool) ($data['success'] ?? false),
            score: isset($data['score']) ? (float) $data['score'] : null,
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
        return 'https://www.google.com/recaptcha/api.js';
    }

    public function responseField(): string
    {
        return 'g-recaptcha-response';
    }
}
