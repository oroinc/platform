import BaseComponent from 'oroui/js/app/components/base/component';
import $ from 'jquery';

const CaptchaTurnstileComponent = BaseComponent.extend({
    apiScript: 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=onloadTurnstileCallback',

    constructor: function CaptchaTurnstileComponent(options) {
        CaptchaTurnstileComponent.__super__.constructor.call(this, options);
    },

    initialize: function(options) {
        CaptchaTurnstileComponent.__super__.initialize.call(this, options);

        window.onloadTurnstileCallback = this.initializeView.bind(this, options);
        if (typeof turnstile == 'undefined') {
            $.getScript(this.apiScript);
        } else {
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
        const $container = $('<div/>');
        $container.insertAfter(options._sourceElement);

        window.turnstile.render($container[0], {
            sitekey: options.site_key,
            callback: function(token) {
                options._sourceElement.val(token);
            }
        });
    }
});

export default CaptchaTurnstileComponent;
