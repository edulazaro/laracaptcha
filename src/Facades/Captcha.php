<?php

namespace EduLazaro\Laracaptcha\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \EduLazaro\Laracaptcha\Contracts\CaptchaDriver driver(string|null $driver = null)
 * @method static \EduLazaro\Laracaptcha\Support\VerificationResult verify(string $token, string|null $ip = null)
 * @method static \EduLazaro\Laracaptcha\Testing\FakeDriver fake(bool $success = true, float|null $score = null)
 * @method static string siteKey()
 * @method static string scriptUrl()
 * @method static string responseField()
 *
 * @see \EduLazaro\Laracaptcha\CaptchaManager
 */
class Captcha extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laracaptcha';
    }
}
