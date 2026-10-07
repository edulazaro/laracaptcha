<?php

namespace EduLazaro\Laracaptcha\Rules;

use Closure;
use EduLazaro\Laracaptcha\CaptchaManager;
use EduLazaro\Laracaptcha\Contracts\ExpectsAction;
use EduLazaro\Laracaptcha\Support\VerificationResult;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Validation rule that checks a captcha token.
 *
 *   'cf-turnstile-response' => ['required', Captcha::make()],
 *   'cf-turnstile-response' => ['required', Captcha::make('recaptcha_v2')],
 *
 * On top of asking the driver, the rule enforces single use: a token that has
 * already passed is refused for the next `laracaptcha.reuse_ttl` minutes, so a
 * token captured from one submission cannot be replayed into another. Turn it
 * off with `laracaptcha.prevent_reuse` if your provider already does this.
 *
 * The four failure messages are translatable and live in `lang/`. Which one the user
 * sees is deliberately coarse, since naming the exact reason would help whoever is
 * probing the form; the provider's own error codes go to the log instead, where only
 * you read them. Turn that off with `laracaptcha.log_failures` if a flood of attempts
 * is filling your log.
 */
class Captcha implements ValidationRule
{
    public function __construct(
        protected ?string $driver = null,
        protected ?string $action = null,
    ) {
    }

    /**
     * Named constructor, so the rule reads well inline in a rules array.
     *
     * A null driver means the configured default, which is what you want
     * unless a specific form uses a different provider. The action must match
     * the one the widget was rendered with, and only providers that stamp
     * their tokens with it (reCAPTCHA v3) can act on it: for the others it is
     * quietly ignored, since they have nothing to compare against.
     */
    public static function make(?string $driver = null, ?string $action = null): static
    {
        return new static($driver, $action);
    }

    /**
     * Run the validation rule.
     *
     * Five ways to fail, in order: the field is empty, the token was already
     * spent, the token was minted for a different action, the provider refused
     * it, or the provider could not be reached (which the driver reports as a
     * refusal). Only on success is the token recorded as spent, so a provider
     * outage does not burn valid tokens.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('laracaptcha::messages.required')->translate();

            return;
        }

        $preventReuse = (bool) config('laracaptcha.prevent_reuse', true);
        $cacheKey = 'laracaptcha|'.md5($value);

        // Replay protection: reject tokens that already passed once.
        if ($preventReuse && Cache::has($cacheKey)) {
            $this->report($this->driver ?? (string) config('laracaptcha.default'), new VerificationResult(
                success: false,
                errorCodes: ['already-used'],
            ));

            $fail('laracaptcha::messages.used')->translate();

            return;
        }

        $driver = app(CaptchaManager::class)->driver($this->driver);

        if ($this->action !== null && $driver instanceof ExpectsAction) {
            $driver = $driver->expectingAction($this->action);
        }

        $result = $driver->verify($value, request()->ip());

        if ($result->failed()) {
            $this->report($driver->name(), $result);

            // An outage is worth saying out loud: "try again in a moment" is true and
            // actionable, while "verification failed" sends the visitor looking for a
            // mistake of their own that they did not make.
            $fail(in_array('unreachable', $result->errorCodes, true)
                ? 'laracaptcha::messages.unreachable'
                : 'laracaptcha::messages.invalid')->translate();

            return;
        }

        if ($preventReuse) {
            Cache::put($cacheKey, true, now()->addMinutes((int) config('laracaptcha.reuse_ttl', 5)));
        }
    }

    /**
     * Leave the provider's reason for refusing in the log.
     *
     * Without it a report of "I cannot sign up" has nothing behind it: every failure
     * looks the same from the outside, and the difference between a wrong site key, a
     * token solved on another host, an outage and an actual bot is only in the codes the
     * provider sent back. An empty field is not reported, since there is nothing to
     * learn from it.
     *
     * @param string $driver
     * @param VerificationResult $result
     * @return void
     */
    protected function report(string $driver, VerificationResult $result): void
    {
        if (! config('laracaptcha.log_failures', true)) {
            return;
        }

        Log::warning('Captcha verification failed', array_filter([
            'driver' => $driver,
            'errors' => $result->errorCodes !== [] ? $result->errorCodes : ['none-reported'],
            'score' => $result->score,
            'ip' => request()->ip(),
            'hostname' => $result->raw['hostname'] ?? null,
        ], fn ($value) => $value !== null));
    }
}
