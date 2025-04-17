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

    initializeView: function(options) {
        window.grecaptcha.ready(this.onRecaptchaReady.bind(this, options));
    },

    onRecaptchaReady: function(options) {
        options._sourceElement.data('captcha-received', false);
        const $form = $(options._sourceElement).closest('form');
        $form.bindFirst('submit', function(e) {
            if (options._sourceElement.data('captcha-received')) {
                options._sourceElement.data('captcha-received', false);

                return;
            }

            e.preventDefault();
            e.stopPropagation();
            window.grecaptcha
                .execute(options.site_key, {action: options.action})
                .then(function(token) {
                    options._sourceElement.data('captcha-received', true);
                    options._sourceElement.val(token);
                    $form.trigger('submit');
                });
        });
    }
});

export default CaptchaReCaptchaComponent;
