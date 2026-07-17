@props([
    'driver' => null,
    'action' => 'submit',
    'theme' => 'auto',
    'size' => null,
])

@php
    $captchaDriver = \EduLazaro\Laracaptcha\Facades\Captcha::driver($driver);
    $captchaName = $captchaDriver->name();
    $siteKey = $captchaDriver->siteKey();
@endphp

@if ($captchaName === 'turnstile')
    <div {{ $attributes->merge(['class' => 'cf-turnstile']) }}
        data-sitekey="{{ $siteKey }}"
        data-theme="{{ $theme }}"
        @if ($size) data-size="{{ $size }}" @endif></div>
    @once('laracaptcha-script')
        <script src="{{ $captchaDriver->scriptUrl() }}" async defer></script>
    @endonce
@elseif ($captchaName === 'recaptcha_v2')
    <div {{ $attributes->merge(['class' => 'g-recaptcha']) }}
        data-sitekey="{{ $siteKey }}"
        data-theme="{{ $theme === 'auto' ? 'light' : $theme }}"
        @if ($size) data-size="{{ $size }}" @endif></div>
    @once('laracaptcha-script')
        <script src="{{ $captchaDriver->scriptUrl() }}" async defer></script>
    @endonce
@elseif ($captchaName === 'recaptcha_v3')
    {{-- Invisible: fills the hidden input with a scored token on form submit. --}}
    <input type="hidden" name="{{ $captchaDriver->responseField() }}" value="" data-laracaptcha-v3 data-action="{{ $action }}">
    @once('laracaptcha-script')
        <script src="{{ $captchaDriver->scriptUrl() }}?render={{ $siteKey }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-laracaptcha-v3]').forEach(function (input) {
                    var form = input.closest('form');
                    if (! form) return;
                    form.addEventListener('submit', function (event) {
                        if (input.value) return;
                        event.preventDefault();
                        grecaptcha.ready(function () {
                            grecaptcha.execute(@json($siteKey), { action: input.dataset.action }).then(function (token) {
                                input.value = token;
                                form.requestSubmit ? form.requestSubmit() : form.submit();
                            });
                        });
                    });
                });
            });
        </script>
    @endonce
@endif
