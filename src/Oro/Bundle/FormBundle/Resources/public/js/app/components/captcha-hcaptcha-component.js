import BaseComponent from 'oroui/js/app/components/base/component';
import $ from 'jquery';

const CaptchaHCaptchaComponent = BaseComponent.extend({
    apiScript: 'https://js.hcaptcha.com/1/api.js?render=explicit&onload=onloadHCaptchaCallback',

    constructor: function CaptchaHCaptchaComponent(options) {
        CaptchaHCaptchaComponent.__super__.constructor.call(this, options);
    },

    initialize: function(options) {
        CaptchaHCaptchaComponent.__super__.initialize.call(this, options);

        window.onloadHCaptchaCallback = this.initializeView.bind(this, options);
        if (typeof hcaptcha == 'undefined') {
            $.getScript(this.apiScript);
        } else {
            window.onloadHCaptchaCallback();
        }
    },

    dispose: function() {
        CaptchaHCaptchaComponent.__super__.dispose.call(this);

        if (typeof window.hcaptcha != 'undefined' && this.captchaWidgetId) {
            window.hcaptcha.reset();
        }
    },

    initializeView(options) {
        const $container = $('<div/>');
        $container.insertAfter(options._sourceElement);

        window.hcaptcha.render($container[0], {
            sitekey: options.site_key,
            callback: function(token) {
                options._sourceElement.val(token);
            }
        });
    }
});

export default CaptchaHCaptchaComponent;
