{{-- Draws every widget on the page, once each, whenever one appears: on load, after
     `wire:navigate` and after every Livewire update. `data-navigate-once` keeps navigation
     from running it twice; a copy inserted by a Livewire update (which never runs) is
     started by the widget's own x-init. --}}
<script data-navigate-once data-laracaptcha-loader>
(function () {
    if (window.Laracaptcha) {
        window.Laracaptcha.scan();
        return;
    }

    var ready = {};
    var requested = {};
    var widgets = [];
    var queued = false;

    function library(provider) {
        if (! ready[provider]) {
            return null;
        }

        return provider === 'turnstile' ? window.turnstile : window.grecaptcha;
    }

    function share(el, token) {
        var name = el.getAttribute('data-model');

        if (! name || ! window.Livewire) {
            return;
        }

        var root = el.closest('[wire\\:id]');
        var wire = root && window.Livewire.find(root.getAttribute('wire:id'));

        if (wire) {
            wire.$set(name, token, el.getAttribute('data-live') === '1');
        }
    }

    function load(provider, url) {
        if (requested[provider]) {
            return;
        }

        requested[provider] = true;

        var callback = 'laracaptchaLoaded_' + provider;
        window[callback] = function () {
            ready[provider] = true;
            scan();
        };

        var script = document.createElement('script');
        script.src = url + (url.indexOf('?') === -1 ? '?' : '&') + (url.indexOf('render=') === -1 ? 'render=explicit&' : '') + 'onload=' + callback;
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }

    function invisible(el, lib, widget) {
        var key = el.getAttribute('data-sitekey');
        var action = el.getAttribute('data-action');

        widget.refresh = function () {
            lib.ready(function () {
                lib.execute(key, { action: action }).then(function (token) {
                    el.value = token;
                    share(el, token);
                });
            });
        };

        if (el.hasAttribute('data-model')) {
            // A v3 token lives two minutes, so a bound one is renewed before it lapses.
            widget.refresh();
            widget.timer = setInterval(widget.refresh, 100000);

            return;
        }

        if (el.form) {
            el.form.addEventListener('submit', function (event) {
                if (el.value) {
                    return;
                }

                event.preventDefault();

                lib.ready(function () {
                    lib.execute(key, { action: action }).then(function (token) {
                        el.value = token;
                        el.form.requestSubmit ? el.form.requestSubmit() : el.form.submit();
                    });
                });
            });
        }
    }

    function draw(el) {
        var provider = el.getAttribute('data-laracaptcha');
        var lib = library(provider);

        if (! lib) {
            load(provider, el.getAttribute('data-script'));

            return;
        }

        el.setAttribute('data-laracaptcha-drawn', '');

        var widget = { el: el, provider: provider };
        widgets.push(widget);

        if (provider === 'recaptcha_v3') {
            invisible(el, lib, widget);

            return;
        }

        var options = {
            sitekey: el.getAttribute('data-sitekey'),
            theme: el.getAttribute('data-theme') || undefined,
            callback: function (token) { share(el, token); },
            'expired-callback': function () { share(el, ''); }
        };

        if (el.getAttribute('data-size')) {
            options.size = el.getAttribute('data-size');
        }

        if (provider === 'turnstile') {
            if (el.getAttribute('data-action')) {
                options.action = el.getAttribute('data-action');
            }

            if (el.getAttribute('data-language')) {
                options.language = el.getAttribute('data-language');
            }
        }

        widget.id = lib.render(el, options);
    }

    function sweep() {
        widgets = widgets.filter(function (widget) {
            if (document.contains(widget.el)) {
                return true;
            }

            if (widget.timer) {
                clearInterval(widget.timer);
            }

            if (widget.provider === 'turnstile' && widget.id !== undefined) {
                try { window.turnstile.remove(widget.id); } catch (e) {}
            }

            return false;
        });
    }

    function scan() {
        sweep();
        document.querySelectorAll('[data-laracaptcha]:not([data-laracaptcha-drawn])').forEach(draw);
    }

    // A token is spent once checked, passed or not, so every widget starts again.
    function reset() {
        sweep();
        widgets.forEach(function (widget) {
            if (widget.refresh) {
                widget.refresh();
            } else if (widget.provider === 'turnstile') {
                window.turnstile.reset(widget.id);
            } else {
                window.grecaptcha.reset(widget.id);
            }
        });
    }

    function later() {
        if (queued) {
            return;
        }

        queued = true;
        setTimeout(function () {
            queued = false;
            scan();
        }, 0);
    }

    function listen() {
        window.Livewire.hook('commit', function (commit) {
            commit.succeed(later);
        });
    }

    window.Laracaptcha = { scan: scan, reset: reset };
    window.addEventListener('laracaptcha-reset', reset);
    document.addEventListener('livewire:navigated', scan);

    if (window.Livewire) {
        listen();
    } else {
        document.addEventListener('livewire:init', listen);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scan);
    } else {
        scan();
    }
})();
</script>
