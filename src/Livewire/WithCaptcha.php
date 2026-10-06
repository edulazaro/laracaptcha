<?php

namespace EduLazaro\Laracaptcha\Livewire;

use EduLazaro\Laracaptcha\Rules\Captcha;
use Illuminate\Support\Facades\Validator;

/**
 * Captcha for a Livewire component: the property the widget writes into, and
 * the check.
 *
 *   use WithCaptcha;
 *
 *   <x-laracaptcha::widget wire:model="captcha" />
 *
 *   public function save(): void
 *   {
 *       $this->verifyCaptcha();
 *       ...
 *   }
 *
 * Every check empties the property and tells the widget to start again,
 * whether it passed or not: the provider spends a token the moment it is
 * checked, so a second submit with the same one fails however human the
 * person is. A failure is a validation error on `captcha`, so `@error` and
 * `assertHasErrors('captcha')` work as for any field.
 */
trait WithCaptcha
{
    /** The token the widget wrote, empty until the challenge is solved. */
    public string $captcha = '';

    /**
     * Check the token and spend it.
     *
     * @param string|null $driver A configured driver name, the default when null.
     * @param string|null $action The action the widget was rendered with, when the provider signs it.
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function verifyCaptcha(?string $driver = null, ?string $action = null): void
    {
        $token = $this->captcha;

        $this->captcha = '';
        $this->dispatch('laracaptcha-reset');

        Validator::make(
            ['captcha' => $token],
            ['captcha' => ['required', Captcha::make($driver, $action)]],
        )->validate();
    }

    /**
     * Check the token only if this session has not passed `$scope` yet.
     *
     * For a flow a person may go through several times in a row, a sign-in
     * where somebody mistypes their address and starts again, where asking
     * every time would only annoy them while a bot still pays once per session.
     *
     * @param string $scope
     * @param string|null $driver
     * @param string|null $action
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function verifyCaptchaOnce(string $scope = 'default', ?string $driver = null, ?string $action = null): void
    {
        if ($this->captchaPassed($scope)) {
            return;
        }

        $this->verifyCaptcha($driver, $action);

        session()->put('laracaptcha.passed.'.$scope, true);
    }

    /**
     * Whether this session has already passed `$scope`, for a view deciding
     * whether to show the widget at all.
     *
     * @param string $scope
     * @return bool
     */
    public function captchaPassed(string $scope = 'default'): bool
    {
        return (bool) session('laracaptcha.passed.'.$scope, false);
    }
}
