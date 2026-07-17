<?php

namespace EduLazaro\Laracaptcha\Contracts;

use EduLazaro\Laracaptcha\Support\VerificationResult;

interface CaptchaDriver
{
    /** Driver identifier, e.g. "turnstile". */
    public function name(): string;

    /** Verify a challenge token against the provider. */
    public function verify(string $token, ?string $ip = null): VerificationResult;

    /** Public site key rendered into the widget. */
    public function siteKey(): string;

    /** Provider JavaScript API url. */
    public function scriptUrl(): string;

    /** Name of the request field carrying the token, e.g. "cf-turnstile-response". */
    public function responseField(): string;
}
