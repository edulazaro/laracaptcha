<?php

namespace EduLazaro\Laracaptcha\Tests;

use EduLazaro\Laracaptcha\Facades\Captcha;
use EduLazaro\Laracaptcha\Tests\Fixtures\SignUpForm;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;

class WithCaptchaTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), LivewireServiceProvider::class];
    }

    public function test_a_solved_challenge_lets_the_action_through(): void
    {
        $fake = Captcha::fake();

        Livewire::test(SignUpForm::class)
            ->set('captcha', 'token-1')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', 1);

        $this->assertSame('token-1', $fake->attempts()[0]['token']);
    }

    public function test_no_token_is_a_validation_error_on_captcha(): void
    {
        $fake = Captcha::fake();

        Livewire::test(SignUpForm::class)
            ->call('send')
            ->assertHasErrors(['captcha' => 'required'])
            ->assertSet('sent', 0);

        $this->assertSame([], $fake->attempts());
    }

    public function test_a_refused_token_is_a_validation_error_on_captcha(): void
    {
        Captcha::fake(success: false);

        Livewire::test(SignUpForm::class)
            ->set('captcha', 'token-1')
            ->call('send')
            ->assertHasErrors('captcha')
            ->assertSet('sent', 0);
    }

    public function test_every_check_spends_the_token_and_resets_the_widget_even_when_it_fails(): void
    {
        Captcha::fake(success: false);

        Livewire::test(SignUpForm::class)
            ->set('captcha', 'token-1')
            ->call('send')
            ->assertSet('captcha', '')
            ->assertDispatched('laracaptcha-reset');
    }

    public function test_once_asks_a_session_a_single_time(): void
    {
        $fake = Captcha::fake();

        $form = Livewire::test(SignUpForm::class)
            ->set('captcha', 'token-1')
            ->call('sendOnce')
            ->assertHasNoErrors()
            ->call('sendOnce')
            ->assertHasNoErrors()
            ->assertSet('sent', 2);

        $this->assertCount(1, $fake->attempts());
        $this->assertTrue($form->instance()->captchaPassed('signup'));
        $this->assertFalse($form->instance()->captchaPassed('other'));
    }

    public function test_once_does_not_remember_a_failure(): void
    {
        Captcha::fake(success: false);

        $form = Livewire::test(SignUpForm::class)
            ->set('captcha', 'token-1')
            ->call('sendOnce')
            ->assertHasErrors('captcha');

        $this->assertFalse($form->instance()->captchaPassed('signup'));
    }

    public function test_the_component_renders_the_bound_widget(): void
    {
        Livewire::test(SignUpForm::class)
            ->assertSeeHtml('data-model="captcha"')
            ->assertSeeHtml('data-laracaptcha="turnstile"');
    }
}
