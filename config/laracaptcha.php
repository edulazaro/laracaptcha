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
