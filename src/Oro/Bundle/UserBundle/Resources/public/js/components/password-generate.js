define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const $ = require('jquery');

    const PasswordGenerateComponent = BaseComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function PasswordGenerateComponent(options) {
            PasswordGenerateComponent.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            this.$el = $(options.checkbox);
            this.passwordInput = $(options.passwordInput);

            this.togglePassword();

            this.$el.click(this.togglePassword.bind(this));
        },

        togglePassword: function() {
            if (this.$el.is(':checked')) {
                this.passwordInput.attr('disabled', true);
            } else {
                this.passwordInput.attr('disabled', false);
            }
        }
    });

    return PasswordGenerateComponent;
});
