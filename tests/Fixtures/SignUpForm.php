<?php

namespace EduLazaro\Laracaptcha\Tests\Fixtures;

use EduLazaro\Laracaptcha\Livewire\WithCaptcha;
use Livewire\Component;

class SignUpForm extends Component
{
    use WithCaptcha;

    public int $sent = 0;

    public function send(): void
    {
        $this->verifyCaptcha();
        $this->sent++;
    }

    public function sendOnce(): void
    {
        $this->verifyCaptchaOnce('signup');
        $this->sent++;
    }

    public function render(): string
    {
        return '<div><x-laracaptcha::widget wire:model="captcha" /></div>';
    }
}
