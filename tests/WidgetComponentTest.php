<?php

namespace EduLazaro\Laracaptcha\Tests;

use EduLazaro\Laracaptcha\Facades\Captcha;
use Illuminate\Support\Facades\Blade;

class WidgetComponentTest extends TestCase
{
    public function test_turnstile_widget_renders(): void
    {
        $html = Blade::render('<x-laracaptcha::widget />');

        $this->assertStringContainsString('cf-turnstile', $html);
        $this->assertStringContainsString('data-laracaptcha="turnstile"', $html);
        $this->assertStringContainsString('data-sitekey="turnstile-site-key"', $html);
        $this->assertStringContainsString('data-script="https://challenges.cloudflare.com/turnstile/v0/api.js"', $html);
    }

    public function test_recaptcha_v2_widget_renders(): void
    {
        $html = Blade::render('<x-laracaptcha::widget driver="recaptcha_v2" />');

        $this->assertStringContainsString('g-recaptcha', $html);
        $this->assertStringContainsString('data-sitekey="v2-site-key"', $html);
        $this->assertStringContainsString('data-script="https://www.google.com/recaptcha/api.js"', $html);
        $this->assertStringContainsString('data-theme="light"', $html, 'v2 has no auto theme.');
    }

    public function test_recaptcha_v3_widget_renders_hidden_input_and_script(): void
    {
        $html = Blade::render('<x-laracaptcha::widget driver="recaptcha_v3" action="register" />');

        $this->assertStringContainsString('name="g-recaptcha-response"', $html);
        $this->assertStringContainsString('data-action="register"', $html);
        $this->assertStringContainsString('api.js?render=v3-site-key', $html);
        $this->assertStringContainsString('lib.execute(key', $html);
    }

    public function test_recaptcha_v3_defaults_to_the_submit_action(): void
    {
        $html = Blade::render('<x-laracaptcha::widget driver="recaptcha_v3" />');

        $this->assertStringContainsString('data-action="submit"', $html);
    }

    public function test_extra_attributes_are_merged(): void
    {
        $html = Blade::render('<x-laracaptcha::widget class="mb-4" theme="dark" />');

        $this->assertStringContainsString('class="cf-turnstile mb-4"', $html);
        $this->assertStringContainsString('data-theme="dark"', $html);
    }

    public function test_defer_marks_the_widget_for_the_page_to_ask_for(): void
    {
        $html = Blade::render('<x-laracaptcha::widget defer />');

        $this->assertStringContainsString('data-laracaptcha-defer', $html);
    }

    public function test_a_widget_is_not_deferred_unless_asked(): void
    {
        // The element only: the loader that follows it names the attribute in its own
        // selector, so the whole render always contains the string.
        $element = fn (string $tag): string => strstr(Blade::render($tag), '<script', true);

        $this->assertStringNotContainsString('data-laracaptcha-defer', $element('<x-laracaptcha::widget />'));
        $this->assertStringNotContainsString('data-laracaptcha-defer', $element('<x-laracaptcha::widget driver="recaptcha_v2" />'));
        $this->assertStringNotContainsString('data-laracaptcha-defer', $element('<x-laracaptcha::widget driver="recaptcha_v3" />'));
    }

    public function test_recaptcha_v3_can_be_deferred_too(): void
    {
        $html = Blade::render('<x-laracaptcha::widget driver="recaptcha_v3" defer />');

        $this->assertStringContainsString('data-laracaptcha-defer', $html);
    }

    public function test_the_page_pass_skips_deferred_widgets_and_can_draw_them_later(): void
    {
        $html = Blade::render('<x-laracaptcha::widget defer />');

        // The selector the loader sweeps the page with has to exclude them, or deferring
        // would only delay the draw until the next scan.
        $this->assertStringContainsString(':not([data-laracaptcha-defer])', $html);
        $this->assertStringContainsString('draw: drawDeferred', $html);
    }

    public function test_a_submit_before_the_challenge_resolves_is_held_and_sent_again(): void
    {
        $html = Blade::render('<x-laracaptcha::widget />');

        $this->assertStringContainsString('hold(el, widget)', $html);
        $this->assertStringContainsString('resume(widget)', $html);
        $this->assertStringContainsString("'error-callback'", $html);
    }

    public function test_the_widget_is_drawn_explicitly_so_it_survives_navigation(): void
    {
        $html = Blade::render('<x-laracaptcha::widget />');

        $this->assertStringContainsString('data-laracaptcha-loader', $html);
        $this->assertStringContainsString('data-navigate-once', $html);
        $this->assertStringContainsString('render=explicit', $html);
        $this->assertStringContainsString("addEventListener('livewire:navigated', scan)", $html);
        $this->assertStringContainsString("window.Livewire.hook('commit'", $html);
    }

    public function test_the_loader_is_rendered_once_however_many_widgets(): void
    {
        $html = Blade::render('<x-laracaptcha::widget /><x-laracaptcha::widget />');

        $this->assertSame(2, substr_count($html, 'data-laracaptcha="turnstile"'));
        $this->assertSame(1, substr_count($html, '<script data-navigate-once data-laracaptcha-loader>'));
    }

    public function test_wire_model_becomes_the_property_the_token_is_written_into(): void
    {
        $html = Blade::render('<x-laracaptcha::widget wire:model="captcha" />');

        $this->assertStringContainsString('data-model="captcha"', $html);
        $this->assertStringContainsString('data-live="0"', $html);
        $this->assertStringContainsString('wire:ignore', $html);
        $this->assertStringContainsString('x-init=', $html);
        $this->assertStringNotContainsString('wire:model', $html, 'Livewire must not bind the div itself.');
    }

    public function test_wire_model_live_writes_at_once(): void
    {
        $html = Blade::render('<x-laracaptcha::widget wire:model.live="token" />');

        $this->assertStringContainsString('data-model="token"', $html);
        $this->assertStringContainsString('data-live="1"', $html);
    }

    public function test_an_unbound_widget_is_left_to_its_form(): void
    {
        $html = Blade::render('<x-laracaptcha::widget />');

        $this->assertStringNotContainsString('data-model=', $html);
        $this->assertStringNotContainsString('wire:ignore', $html);
    }

    public function test_turnstile_takes_an_action_and_a_language(): void
    {
        $html = Blade::render('<x-laracaptcha::widget action="login" language="es" />');

        $this->assertStringContainsString('data-action="login"', $html);
        $this->assertStringContainsString('data-language="es"', $html);
    }

    public function test_the_fake_renders_nothing(): void
    {
        Captcha::fake();

        $this->assertSame('', trim(Blade::render('<x-laracaptcha::widget wire:model="captcha" />')));
    }
}
