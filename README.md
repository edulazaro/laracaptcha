![Laracaptcha](art/banner.png)

# Laracaptcha

Driver-based captcha for Laravel. One API for **Cloudflare Turnstile** and **Google reCAPTCHA v2/v3**: switch providers by changing one env variable, no code changes.

## Installation

```bash
composer require edulazaro/laracaptcha
```

Set your driver and keys in `.env`:

```env
CAPTCHA_DRIVER=turnstile

TURNSTILE_KEY=0x4AAA...
TURNSTILE_SECRET=0x4AAA...

# Or for reCAPTCHA (v2 / v3):
# CAPTCHA_DRIVER=recaptcha_v2
# RECAPTCHA_KEY=...
# RECAPTCHA_SECRET=...
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag=laracaptcha-config
```

## Usage

### In a form

Drop the widget component inside any form. It renders the right markup and loads the provider script for the configured driver:

```blade
<form method="POST" action="/register">
    @csrf
    ...
    <x-laracaptcha::widget />
    <button type="submit">Register</button>
</form>
```

For reCAPTCHA v3 the widget is invisible and tokens are generated on submit; tag the action so the token is bound to this form: `<x-laracaptcha::widget action="register" />`. The default is `submit`.

Other options: `theme` (`auto`, `light`, `dark`), `size`, and for Turnstile `action` and `language` (the browser's when left out).

The widget is drawn by the package's own script, not by the provider scanning the page on load, so it also appears on a page reached with `wire:navigate` and when a Livewire update reveals it.

### In a Livewire component

Livewire submits the component's properties, not the form's inputs, so the token the provider writes into its hidden field never reaches the component. Bind the widget instead, and use the trait:

```blade
<form wire:submit="register">
    ...
    <x-laracaptcha::widget wire:model="captcha" />
    @error('captcha') <p>{{ $message }}</p> @enderror
    <button type="submit">Register</button>
</form>
```

```php
use EduLazaro\Laracaptcha\Livewire\WithCaptcha;

class Register extends Component
{
    use WithCaptcha;

    public function register(): void
    {
        $this->verifyCaptcha();

        // ...
    }
}
```

The token is written into `$captcha` when the challenge is solved and emptied when it expires. `verifyCaptcha()` checks it, spends it and resets the widget whether it passed or not, since a provider refuses a token it has already seen. A failure is a validation error on `captcha`. Pass a driver and an action when the form needs them: `$this->verifyCaptcha('turnstile', 'register')`.

For a flow somebody may repeat, like a sign-in where they mistype the address and start again, ask once per session:

```php
$this->verifyCaptchaOnce('login');
```

```blade
@unless ($this->captchaPassed('login'))
    <x-laracaptcha::widget wire:model="captcha" />
@endunless
```

`wire:model.live` sends the token at once instead of with the next request.

### Validating the token

Use the validation rule on the token field. The field name depends on the provider (`cf-turnstile-response` for Turnstile, `g-recaptcha-response` for reCAPTCHA); get it dynamically with `Captcha::responseField()`:

```php
use EduLazaro\Laracaptcha\Rules\Captcha;
use EduLazaro\Laracaptcha\Facades\Captcha as CaptchaFacade;

$request->validate([
    CaptchaFacade::responseField() => ['required', new Captcha],
]);
```

The rule includes replay protection: a token that already passed once is rejected (configurable via `prevent_reuse` / `reuse_ttl`).

#### Binding a token to its action (reCAPTCHA v3)

A v3 token carries the action the widget minted it for. Without checking it, a token harvested from a low value form is good for a critical one, so pass the same action to the rule that you gave the widget:

```php
use EduLazaro\Laracaptcha\Rules\Captcha;

$request->validate([
    'g-recaptcha-response' => ['required', Captcha::make('recaptcha_v3', 'register')],
]);
```

A token minted for anything else is rejected with the `action-mismatch` error code, and so is a response that carries no action at all. Leave the action out and nothing is checked, which is the default. Turnstile signs the action too (since 1.1): give the widget and the rule the same one. reCAPTCHA v2 has no action and ignores it.

#### Binding a token to your site

Every provider reports the hostname the challenge was solved on. List yours and a token solved anywhere else is refused with `hostname-mismatch`:

```env
CAPTCHA_HOSTNAMES=example.com,www.example.com
```

Empty by default, because Cloudflare's test keys report `example.com` whatever page they run on.

Outside the rule, ask the driver directly:

```php
Captcha::driver('recaptcha_v3')->expectingAction('register')->verify($token);
```

### Verifying manually

```php
use EduLazaro\Laracaptcha\Facades\Captcha;

$result = Captcha::verify($token, $request->ip());

$result->success;    // bool
$result->score;      // float|null (reCAPTCHA v3)
$result->errorCodes; // array
```

Use a specific driver regardless of the default: `Captcha::driver('recaptcha_v3')->verify($token)`.

### Testing

```php
use EduLazaro\Laracaptcha\Facades\Captcha;

$fake = Captcha::fake();                 // all verifications pass
$fake = Captcha::fake(success: false);   // all verifications fail
$fake = Captcha::fake(score: 0.9);       // pass with a score

$fake->attempts(); // recorded [token, ip] pairs
```

No HTTP requests are made while faked, and the widget renders nothing. In a Livewire test, set the property and call the action:

```php
Captcha::fake();

Livewire::test(Register::class)
    ->set('captcha', 'any-token')
    ->call('register')
    ->assertHasNoErrors();
```

### Upgrading from 1.0

The widget became a class component: run `php artisan view:clear` after updating, or views compiled against 1.0 keep rendering the old one. A rule given an action now checks it on Turnstile as well.

### Dev keys

Cloudflare publishes always-pass test keys for local development: sitekey `1x00000000000000000000AA`, secret `1x0000000000000000000000000000000AA`.

## Sponsors

Laracaptcha is supported by the following sponsors. Thank you for keeping it growing:

<p>
  <a href="https://kenodo.com"><img src="art/logo-kenodo.png" width="24" alt="Kenodo"></a>&nbsp;<a href="https://kenodo.com">Kenodo</a>&nbsp;&nbsp;&nbsp;&nbsp;
  <a href="https://andorradev.com"><img src="art/logo-andorradev.png" width="24" alt="AndorraDev"></a>&nbsp;<a href="https://andorradev.com">AndorraDev</a>
</p>

## Author

Created by [Edu Lazaro](https://edulazaro.com)

## License

Laracaptcha is open-sourced software licensed under the [MIT license](LICENSE).
