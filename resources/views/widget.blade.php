{{-- Drawn by the script below, never by the provider's own scan of the page: see
     EduLazaro\Laracaptcha\View\Components\Widget. --}}
@if ($script !== '')
    @if ($provider === 'recaptcha_v3')
        <input type="hidden" name="{{ $field }}" value=""
            {{ $attributes->whereDoesntStartWith('wire:model') }}
            data-laracaptcha="recaptcha_v3"
            data-script="{{ $script }}"
            data-sitekey="{{ $siteKey }}"
            data-action="{{ $actionValue }}"
            data-laracaptcha-v3
            @if ($bound) wire:ignore x-data x-init="{{ $boot }}" data-model="{{ $bound['name'] }}" data-live="{{ $bound['live'] ? '1' : '0' }}" @endif>
    @else
        <div {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => $provider === 'turnstile' ? 'cf-turnstile' : 'g-recaptcha']) }}
            data-laracaptcha="{{ $provider }}"
            data-script="{{ $script }}"
            data-sitekey="{{ $siteKey }}"
            data-theme="{{ $themeValue }}"
            @if ($size) data-size="{{ $size }}" @endif
            @if ($actionValue) data-action="{{ $actionValue }}" @endif
            @if ($language) data-language="{{ $language }}" @endif
            @if ($bound) wire:ignore x-data x-init="{{ $boot }}" data-model="{{ $bound['name'] }}" data-live="{{ $bound['live'] ? '1' : '0' }}" @endif></div>
    @endif

    @once('laracaptcha-loader')
        @include('laracaptcha::loader')
    @endonce
@endif
