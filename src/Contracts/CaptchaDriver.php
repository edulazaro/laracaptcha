<?php

namespace EduLazaro\Laracaptcha\Contracts;

use EduLazaro\Laracaptcha\Support\VerificationResult;

/**
 * A captcha provider, as seen by the rest of the package.
 *
 * Implement this to add a provider the package does not ship, then register it
 * with `Captcha::extend('my-provider', fn () => new MyDriver($config))`, where
 * the closure decides where the credentials come from. The shipped drivers take
 * their own slice of `config('laracaptcha.drivers')` in the constructor.
 *
 * A driver covers both halves of the flow: telling the widget how to render
 * itself (siteKey, scriptUrl, responseField) and checking the token the widget
 * produces (verify).
 *
 * Implementations must fail closed. A driver that cannot reach its provider
 * returns an unsuccessful result, it does not throw and it never lets the
 * request through by default.
 */
interface CaptchaDriver
{
    /**
     * Driver identifier, e.g. "turnstile".
     *
     * For the caller's benefit only: logging, or branching in a view that
     * renders one provider's markup differently. The package resolves drivers
     * by their config key, never by this.
     */
    public function name(): string;

    /**
     * Verify a challenge token against the provider.
     *
     * Never throws: a refused token, an expired one, a malformed response and
     * an unreachable provider all come back as a failed VerificationResult, so
     * the caller only has to look at `passed()`. The last of those is the one
     * worth being deliberate about, since letting a connection error out would
     * turn a provider outage into a 500 on the form: the shipped drivers report
     * it as the "unreachable" error code. The IP is optional and is forwarded
     * to providers that use it as an extra signal.
     */
    public function verify(string $token, ?string $ip = null): VerificationResult;

    /** Public site key rendered into the widget. */
    public function siteKey(): string;

    /**
     * Provider JavaScript API url, without the site key.
     *
     * The widget appends whatever query string the provider needs, so return
     * the bare endpoint. An empty string means the driver needs no script,
     * which is what the fake driver does in tests.
     */
    public function scriptUrl(): string;

    /**
     * Name of the request field carrying the token, e.g. "cf-turnstile-response".
     *
     * This is the name the provider's own script gives the hidden input, so it
     * is dictated by the provider and not a choice. It is also the field name
     * the validation rule expects to find in the request.
     */
    public function responseField(): string;
}
