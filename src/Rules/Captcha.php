<?php

namespace EduLazaro\Laracaptcha\Rules;

use Closure;
use EduLazaro\Laracaptcha\CaptchaManager;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Cache;

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
 * The three failure messages are translatable and live in `lang/`.
 */
class Captcha implements ValidationRule
{
    public function __construct(protected ?string $driver = null)
    {
    }

    /**
     * Named constructor, so the rule reads well inline in a rules array.
     *
     * A null driver means the configured default, which is what you want
     * unless a specific form uses a different provider.
     */
    public static function make(?string $driver = null): static
    {
        return new static($driver);
    }

    /**
     * Run the validation rule.
     *
     * Four ways to fail, in order: the field is empty, the token was already
     * spent, the provider refused it, or the provider could not be reached
     * (which the driver reports as a refusal). Only on success is the token
     * recorded as spent, so a provider outage does not burn valid tokens.
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
            $fail('laracaptcha::messages.used')->translate();

            return;
        }

        $result = app(CaptchaManager::class)
            ->driver($this->driver)
            ->verify($value, request()->ip());

        if ($result->failed()) {
            $fail('laracaptcha::messages.invalid')->translate();

            return;
        }

        if ($preventReuse) {
            Cache::put($cacheKey, true, now()->addMinutes((int) config('laracaptcha.reuse_ttl', 5)));
        }
    }
}
