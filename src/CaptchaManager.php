<?php

namespace EduLazaro\Laracaptcha;

use EduLazaro\Laracaptcha\Drivers\RecaptchaV2Driver;
use EduLazaro\Laracaptcha\Drivers\RecaptchaV3Driver;
use EduLazaro\Laracaptcha\Drivers\TurnstileDriver;
use EduLazaro\Laracaptcha\Testing\FakeDriver;
use Illuminate\Support\Manager;

/**
 * Resolves and caches the configured captcha drivers.
 *
 * A standard Laravel Manager, so `Captcha::driver()` gives the default from
 * `laracaptcha.default`, `Captcha::driver('recaptcha_v2')` gives a named one,
 * and any method not defined here is forwarded to the resolved driver. Each
 * driver is built once and reused for the rest of the request.
 */
class CaptchaManager extends Manager
{
    /** Driver used when none is named, from `laracaptcha.default`. */
    public function getDefaultDriver(): string
    {
        return $this->config->get('laracaptcha.default', 'turnstile');
    }

    /** Called by the Manager when the "turnstile" driver is resolved. */
    protected function createTurnstileDriver(): TurnstileDriver
    {
        return new TurnstileDriver($this->config->get('laracaptcha.drivers.turnstile', []));
    }

    /** Called by the Manager when the "recaptcha_v2" driver is resolved. */
    protected function createRecaptchaV2Driver(): RecaptchaV2Driver
    {
        return new RecaptchaV2Driver($this->config->get('laracaptcha.drivers.recaptcha_v2', []));
    }

    /** Called by the Manager when the "recaptcha_v3" driver is resolved. */
    protected function createRecaptchaV3Driver(): RecaptchaV3Driver
    {
        return new RecaptchaV3Driver($this->config->get('laracaptcha.drivers.recaptcha_v3', []));
    }

    /**
     * Replace every driver with a fake for testing. No HTTP calls are made;
     * verifications succeed (or fail) as configured and attempts are recorded.
     *
     * Every configured name plus the default one is pointed at the SAME fake,
     * so a test that fakes once covers code that asks for a driver by name.
     * The returned instance is the one to assert against.
     */
    public function fake(bool $success = true, ?float $score = null): FakeDriver
    {
        $fake = new FakeDriver($success, $score);

        $names = array_keys($this->config->get('laracaptcha.drivers', []));
        $names[] = $this->getDefaultDriver();

        foreach (array_unique($names) as $name) {
            $this->drivers[$name] = $fake;
        }

        return $fake;
    }
}
