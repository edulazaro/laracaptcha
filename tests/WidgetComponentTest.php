<?php

namespace EduLazaro\Laracaptcha\Tests;

use Illuminate\Support\Facades\Blade;

class WidgetComponentTest extends TestCase
{
    public function test_turnstile_widget_renders(): void
    {
        $html = Blade::render('<x-laracaptcha::widget />');

        $this->assertStringContainsString('cf-turnstile', $html);
        $this->assertStringContainsString('data-sitekey="turnstile-site-key"', $html);
        $this->assertStringContainsString('challenges.cloudflare.com/turnstile/v0/api.js', $html);
    }

    public function test_recaptcha_v2_widget_renders(): void
    {
        $html = Blade::render('<x-laracaptcha::widget driver="recaptcha_v2" />');

        $this->assertStringContainsString('g-recaptcha', $html);
        $this->assertStringContainsString('data-sitekey="v2-site-key"', $html);
        $this->assertStringContainsString('www.google.com/recaptcha/api.js', $html);
    }

    public function test_recaptcha_v3_widget_renders_hidden_input_and_script(): void
    {
        $html = Blade::render('<x-laracaptcha::widget driver="recaptcha_v3" action="register" />');

        $this->assertStringContainsString('name="g-recaptcha-response"', $html);
        $this->assertStringContainsString('data-action="register"', $html);
        $this->assertStringContainsString('grecaptcha.execute', $html);
        $this->assertStringContainsString('api.js?render=v3-site-key', $html);
    }

    public function test_extra_attributes_are_merged(): void
    {
        $html = Blade::render('<x-laracaptcha::widget class="mb-4" theme="dark" />');

        $this->assertStringContainsString('class="cf-turnstile mb-4"', $html);
        $this->assertStringContainsString('data-theme="dark"', $html);
    }
}
