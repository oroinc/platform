import BaseComponent from 'oroui/js/app/components/base/component';
import $ from 'jquery';

const CaptchaReCaptchaComponent = BaseComponent.extend({
    apiScript: 'https://www.google.com/recaptcha/api.js',

    constructor: function CaptchaReCaptchaComponent(options) {
        CaptchaReCaptchaComponent.__super__.constructor.call(this, options);
    },

    initialize: function(options) {
        CaptchaReCaptchaComponent.__super__.initialize.call(this, options);

        $.getScript(this.apiScript + '?render=' + options.site_key)
            .done(this.initializeView.bind(this, options));
    },

    initializeView(options) {
        window.grecaptcha.ready(function() {
            window.grecaptcha
                .execute(options.site_key, {action: options.action})
                .then(function(token) {
                    options._sourceElement.val(token);
                });
        });
    }
});

export default CaptchaReCaptchaComponent;
