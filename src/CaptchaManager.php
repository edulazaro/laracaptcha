<?php

namespace EduLazaro\Laracaptcha;

use EduLazaro\Laracaptcha\Drivers\RecaptchaV2Driver;
use EduLazaro\Laracaptcha\Drivers\RecaptchaV3Driver;
use EduLazaro\Laracaptcha\Drivers\TurnstileDriver;
use EduLazaro\Laracaptcha\Testing\FakeDriver;
use Illuminate\Support\Manager;

class CaptchaManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('laracaptcha.default', 'turnstile');
    }

    protected function createTurnstileDriver(): TurnstileDriver
    {
        return new TurnstileDriver($this->config->get('laracaptcha.drivers.turnstile', []));
    }

    protected function createRecaptchaV2Driver(): RecaptchaV2Driver
    {
        return new RecaptchaV2Driver($this->config->get('laracaptcha.drivers.recaptcha_v2', []));
    }

    protected function createRecaptchaV3Driver(): RecaptchaV3Driver
    {
        return new RecaptchaV3Driver($this->config->get('laracaptcha.drivers.recaptcha_v3', []));
    }

    /**
     * Replace every driver with a fake for testing. No HTTP calls are made;
     * verifications succeed (or fail) as configured and attempts are recorded.
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
