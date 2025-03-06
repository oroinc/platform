import BaseComponent from 'oroui/js/app/components/base/component';
import $ from 'jquery';
import scriptjs from 'scriptjs';
import _ from 'underscore';

const CaptchaTurnstileComponent = BaseComponent.extend({
    apiScript: 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=onloadTurnstileCallback',

    constructor: function CaptchaTurnstileComponent(options) {
        CaptchaTurnstileComponent.__super__.constructor.call(this, options);
    },

    initialize: function(options) {
        CaptchaTurnstileComponent.__super__.initialize.call(this, options);

        if (typeof window.loadTurnstileCallbacks == 'undefined') {
            window.loadTurnstileCallbacks = [];
        }
        window.loadTurnstileCallbacks.push(this.initializeView.bind(this, options));

        if (typeof window.onloadTurnstileCallback == 'undefined') {
            window.onloadTurnstileCallback = function() {
                while (window.loadTurnstileCallbacks.length > 0) {
                    window.loadTurnstileCallbacks.pop()();
                }
            };

            if (typeof window.turnstile == 'undefined') {
                scriptjs(this.apiScript);
            }
        }

        if (typeof window.turnstile != 'undefined') {
            window.onloadTurnstileCallback();
        }
    },

    dispose: function() {
        CaptchaTurnstileComponent.__super__.dispose.call(this);

        if (typeof window.turnstile != 'undefined') {
            window.turnstile.remove();
        }
    },

    initializeView(options) {
        const $sourceEl = $(options._sourceElement);
        const $container = $('<div id="' + $sourceEl.attr('id') + '_container"/>');
        $container.insertAfter(options._sourceElement);

        const allowedOptions = [
            'action',
            'cdata',
            'theme',
            'language',
            'tabindex',
            'size',
            'retry',
            'retry-interval',
            'refresh-expired',
            'refresh-timeout',
            'appearance',
            'feedback-enabled'
        ];
        const captchaOptions = Object.assign({
            sitekey: options.site_key,
            callback: function(token) {
                options._sourceElement.val(token);
            }
        }, _.pick($sourceEl.data(), allowedOptions));

        window.turnstile.render($container[0], captchaOptions);
    }
});

export default CaptchaTurnstileComponent;
