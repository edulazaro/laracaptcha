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

A token minted for anything else is rejected with the `action-mismatch` error code, and so is a response that carries no action at all. Leave the action out and nothing is checked, which is the default. Providers whose tokens are not stamped with an action, Turnstile among them, ignore it.

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

No HTTP requests are made while faked.

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
