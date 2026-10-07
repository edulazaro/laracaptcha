{{-- Draws every widget on the page, once each, whenever one appears: on load, after
     `wire:navigate` and after every Livewire update. `data-navigate-once` keeps navigation
     from running it twice; a copy inserted by a Livewire update (which never runs) is
     started by the widget's own x-init.

     Two things it does beyond drawing. A widget marked `defer` is left for
     `Laracaptcha.draw(...)` to ask for, so markup that is present but hidden does not
     fetch the provider's script for every visitor. And a submit made before the challenge
     has resolved is held back and sent again from the callback, so the visitor presses the
     button once instead of meeting an empty token and a server-side refusal. --}}
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

    function send(form) {
        form.requestSubmit ? form.requestSubmit() : form.submit();
    }

    // The token the provider is holding for this widget, '' while unsolved. Asking the
    // library beats reading its hidden input, which only some providers write.
    function response(widget) {
        var lib = library(widget.provider);

        try {
            return (lib && widget.id !== undefined ? lib.getResponse(widget.id) : '') || '';
        } catch (e) {
            return '';
        }
    }

    // Holds back a submit made before the challenge resolved, to send it again once it
    // does. Without this the form posts an empty token, the server refuses it and the
    // visitor has to press the button a second time: the same reason `invisible()` already
    // gates reCAPTCHA v3. A bound widget is left alone, since Livewire submits the
    // component's state and never the form.
    function hold(el, widget) {
        var form = el.closest('form');

        if (! form || el.hasAttribute('data-model')) {
            return;
        }

        widget.form = form;

        form.addEventListener('submit', function (event) {
            if (widget.gaveUp || response(widget)) {
                return;
            }

            event.preventDefault();
            widget.waiting = true;
        });
    }

    // Sends the held submit on. Called when the challenge resolves, and also when it gives
    // up: there the form posts without a token and the server answers with the captcha
    // error, which tells the visitor more than a button that does nothing. `gaveUp` keeps
    // this widget from holding anything again, so a provider that keeps failing cannot
    // leave the form unusable.
    function resume(widget, gaveUp) {
        if (gaveUp) {
            widget.gaveUp = true;
        }

        if (! widget.waiting || ! widget.form || ! document.contains(widget.form)) {
            return;
        }

        widget.waiting = false;
        send(widget.form);
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
                        send(el.form);
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
            callback: function (token) { share(el, token); resume(widget); },
            // Not a reason to resume: the provider renews an expired token on its own and
            // the callback above fires again, which is what sends the held submit.
            'expired-callback': function () { share(el, ''); },
            'error-callback': function () { share(el, ''); resume(widget, true); }
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

            options['timeout-callback'] = function () { resume(widget, true); };
        }

        widget.id = lib.render(el, options);

        hold(el, widget);
    }

    // Draws the widgets inside `target` that the page's own pass left out because they
    // carry `defer`. Takes an element, a selector or a list, and either the widget itself
    // or anything containing it, so opening a modal is `Laracaptcha.draw('#myModal')`.
    function drawDeferred(target) {
        var roots = typeof target === 'string'
            ? document.querySelectorAll(target)
            : (target instanceof Element ? [target] : (target || []));

        Array.prototype.forEach.call(roots, function (root) {
            var nodes = root.matches && root.matches('[data-laracaptcha]')
                ? [root]
                : root.querySelectorAll('[data-laracaptcha]');

            Array.prototype.forEach.call(nodes, function (node) {
                // Dropped before drawing, so the scan that follows a script finishing to
                // load picks the widget up instead of skipping it again.
                node.removeAttribute('data-laracaptcha-defer');

                if (! node.hasAttribute('data-laracaptcha-drawn')) {
                    draw(node);
                }
            });
        });
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
        document.querySelectorAll('[data-laracaptcha]:not([data-laracaptcha-drawn]):not([data-laracaptcha-defer])').forEach(draw);
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

    window.Laracaptcha = { scan: scan, reset: reset, draw: drawDeferred };
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
