<?php

namespace EduLazaro\Laracaptcha\Rules;

use Closure;
use EduLazaro\Laracaptcha\CaptchaManager;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Cache;

class Captcha implements ValidationRule
{
    public function __construct(protected ?string $driver = null)
    {
    }

    public static function make(?string $driver = null): static
    {
        return new static($driver);
    }

    /**
     * Run the validation rule.
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
