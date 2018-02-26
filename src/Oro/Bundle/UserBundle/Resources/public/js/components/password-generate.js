define(function(require) {
    'use strict';

    var PasswordGenerateComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');
    var _ = require('underscore');

    PasswordGenerateComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function PasswordGenerateComponent() {
            PasswordGenerateComponent.__super__.constructor.apply(this, arguments);
        },

        initialize: function(options) {
            this.$el = $(options.checkbox);
            this.passwordInput = $(options.passwordInput);

            this.togglePassword();

            this.$el.click(_.bind(this.togglePassword, this));
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
