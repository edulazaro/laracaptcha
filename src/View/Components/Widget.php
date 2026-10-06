<?php

namespace EduLazaro\Laracaptcha\View\Components;

use Closure;
use EduLazaro\Laracaptcha\CaptchaManager;
use EduLazaro\Laracaptcha\Contracts\CaptchaDriver;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

/**
 * `<x-laracaptcha::widget />`: the provider's challenge, drawn in place.
 *
 *   <x-laracaptcha::widget />                         inside a plain form
 *   <x-laracaptcha::widget wire:model="captcha" />    inside a Livewire component
 *
 * Every widget is rendered explicitly by the package's own script rather than
 * by the provider scanning the page once on load. A scan on load misses every
 * widget that arrives later: a page reached with `wire:navigate`, or a form
 * step a Livewire update reveals. The script draws on load, after navigation
 * and after each Livewire update, so a widget is drawn wherever it appears.
 *
 * With `wire:model` the token is written into that property as soon as the
 * challenge is solved, and emptied when it expires, because Livewire submits
 * its properties and not the form's inputs: the hidden field the provider
 * fills never reaches the component.
 */
class Widget extends Component
{
    /**
     * @param string|null $driver A configured driver name, the default when null.
     * @param string|null $action Signed into the token (Turnstile, reCAPTCHA v3), for the rule to compare.
     * @param string $theme `auto`, `light` or `dark`.
     * @param string|null $size The provider's size (`normal`, `compact`, `flexible`…).
     * @param string|null $language Turnstile's language, `auto` (the browser's) when null.
     */
    public function __construct(
        public ?string $driver = null,
        public ?string $action = null,
        public string $theme = 'auto',
        public ?string $size = null,
        public ?string $language = null,
    ) {
    }

    /**
     * Run when a bound widget enters the page. Starts the loader that a Livewire
     * update inserted without running (scripts arriving in a morph never run),
     * or asks the one already running to draw.
     */
    public const BOOT = "window.Laracaptcha ? window.Laracaptcha.scan() : document.querySelectorAll('script[data-laracaptcha-loader]').forEach(function (s) { var c = document.createElement('script'); c.textContent = s.textContent; document.head.appendChild(c); })";

    /**
     * A closure, because the attributes (`wire:model` among them) only reach a
     * component after `render()` runs; the closure is called with them.
     *
     * @return Closure
     */
    public function render(): Closure
    {
        return function (array $data): View {
            $captcha = app(CaptchaManager::class)->driver($this->driver);

            return view('laracaptcha::widget', [
                'bound' => $this->binding($data['attributes'] ?? new ComponentAttributeBag()),
                'boot' => self::BOOT,
                'provider' => $captcha->name(),
                'siteKey' => $captcha->siteKey(),
                'script' => $this->script($captcha),
                'themeValue' => $captcha->name() === 'turnstile' ? $this->theme : ($this->theme === 'dark' ? 'dark' : 'light'),
                'actionValue' => $captcha->name() === 'recaptcha_v3' ? ($this->action ?? 'submit') : $this->action,
                'field' => $captcha->responseField(),
            ]);
        };
    }

    /**
     * The Livewire property the token goes into, and whether writing it should
     * send a request at once (`wire:model.live`) or wait for the next one.
     *
     * @param ComponentAttributeBag $attributes
     * @return array{name: string, live: bool}|null
     */
    public function binding(ComponentAttributeBag $attributes): ?array
    {
        foreach ($attributes->getAttributes() as $key => $value) {
            if ($key === 'wire:model' || str_starts_with($key, 'wire:model.')) {
                return ['name' => (string) $value, 'live' => str_contains($key, '.live')];
            }
        }

        return null;
    }

    /**
     * The provider's script, with the site key reCAPTCHA v3 needs in its address.
     *
     * @param CaptchaDriver $captcha
     * @return string
     */
    protected function script(CaptchaDriver $captcha): string
    {
        $url = $captcha->scriptUrl();

        if ($url === '' || $captcha->name() !== 'recaptcha_v3') {
            return $url;
        }

        return $url.'?render='.urlencode($captcha->siteKey());
    }
}
