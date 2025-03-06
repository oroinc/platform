import BaseComponent from 'oroui/js/app/components/base/component';
import $ from 'jquery';
import scriptjs from 'scriptjs';
import _ from 'underscore';

const CaptchaHCaptchaComponent = BaseComponent.extend({
    apiScript: 'https://js.hcaptcha.com/1/api.js?onload=onloadHCaptchaCallback&render=explicit',

    constructor: function CaptchaHCaptchaComponent(options) {
        CaptchaHCaptchaComponent.__super__.constructor.call(this, options);
    },

    initialize: function(options) {
        CaptchaHCaptchaComponent.__super__.initialize.call(this, options);

        if (typeof window.loadHCaptchaCallbacks == 'undefined') {
            window.loadHCaptchaCallbacks = [];
        }
        window.loadHCaptchaCallbacks.push(this.initializeView.bind(this, options));

        if (typeof window.onloadHCaptchaCallback == 'undefined') {
            window.onloadHCaptchaCallback = function() {
                while (window.loadHCaptchaCallbacks.length > 0) {
                    window.loadHCaptchaCallbacks.pop()();
                }
            };

            if (typeof window.hcaptcha == 'undefined') {
                scriptjs(this.apiScript);
            }
        }

        if (typeof window.hcaptcha != 'undefined') {
            window.onloadHCaptchaCallback();
        }
    },

    dispose: function() {
        CaptchaHCaptchaComponent.__super__.dispose.call(this);

        delete window.loadHCaptchaCallbacks;
        if (typeof window.hcaptcha != 'undefined' && this.captchaWidgetId) {
            window.hcaptcha.reset();
        }
    },

    initializeView(options) {
        const $sourceEl = $(options._sourceElement);
        const $container = $('<div id="' + $sourceEl.attr('id') + '_container"/>');
        $container.insertAfter(options._sourceElement);

        const allowedOptions = ['size', 'theme', 'orientation', 'tabindex'];
        const captchaOptions = Object.assign({
            sitekey: options.site_key,
            callback: function(token) {
                options._sourceElement.val(token);
            }
        }, _.pick($sourceEl.data(), allowedOptions));

        window.hcaptcha.render($container[0], captchaOptions);
    }
});

export default CaptchaHCaptchaComponent;
