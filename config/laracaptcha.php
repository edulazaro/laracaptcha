<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default captcha driver
    |--------------------------------------------------------------------------
    |
    | Supported: "turnstile", "recaptcha_v2", "recaptcha_v3"
    |
    */

    'default' => env('CAPTCHA_DRIVER', 'turnstile'),

    /*
    |--------------------------------------------------------------------------
    | Token reuse protection
    |--------------------------------------------------------------------------
    |
    | When enabled, a verified token is remembered in cache for "reuse_ttl"
    | minutes and rejected if submitted again (replay protection).
    |
    */

    'prevent_reuse' => env('CAPTCHA_PREVENT_REUSE', true),

    'reuse_ttl' => 5,

    /*
    |--------------------------------------------------------------------------
    | Log failed verifications
    |--------------------------------------------------------------------------
    |
    | Off by default: the package writes nothing anywhere unless you ask it to.
    |
    | Turn it on and every refusal is written to the log with the provider's own
    | error codes. The visitor is only ever told that the captcha failed, on
    | purpose, so this is the one place the real reason appears: a wrong secret,
    | a token solved on another host, an outage, a replay. Worth switching on
    | while chasing a report of "I cannot sign up", and off again afterwards,
    | since a flood of attempts against a public form writes a line each.
    |
    | CAPTCHA_LOG_FAILURES=true
    |
    */

    'log_failures' => env('CAPTCHA_LOG_FAILURES', false),

    /*
    |--------------------------------------------------------------------------
    | Provider timeout
    |--------------------------------------------------------------------------
    |
    | Seconds to wait for the provider before giving up. This call sits in the
    | middle of a form submission, so a slow provider is a slow page: keep it
    | short. Giving up counts as a failed verification, never as a pass.
    |
    */

    'timeout' => 5,

    /*
    |--------------------------------------------------------------------------
    | Hostnames
    |--------------------------------------------------------------------------
    |
    | The sites a token may have been solved on. Every provider reports the
    | hostname the widget ran on, and without comparing it a token solved on
    | another site that shares your site key passes here too. Empty means no
    | check, which is the default because Cloudflare's test keys report
    | "example.com" whatever page they ran on.
    |
    | CAPTCHA_HOSTNAMES=example.com,www.example.com
    |
    */

    'hostnames' => array_values(array_filter(array_map('trim', explode(',', (string) env('CAPTCHA_HOSTNAMES', ''))))),

    /*
    |--------------------------------------------------------------------------
    | Drivers
    |--------------------------------------------------------------------------
    */

    'drivers' => [

        'turnstile' => [
            'key' => env('TURNSTILE_KEY', ''),
            'secret' => env('TURNSTILE_SECRET', ''),
        ],

        'recaptcha_v2' => [
            'key' => env('RECAPTCHA_KEY', ''),
            'secret' => env('RECAPTCHA_SECRET', ''),
        ],

        'recaptcha_v3' => [
            'key' => env('RECAPTCHA_V3_KEY', env('RECAPTCHA_KEY', '')),
            'secret' => env('RECAPTCHA_V3_SECRET', env('RECAPTCHA_SECRET', '')),
            // Verifications with a score below this threshold fail even if
            // Google reports success. Range 0.0 (bot) to 1.0 (human).
            'min_score' => (float) env('RECAPTCHA_V3_MIN_SCORE', 0.5),
        ],

    ],

];
