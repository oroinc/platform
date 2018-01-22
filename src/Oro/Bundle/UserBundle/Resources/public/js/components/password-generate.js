/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var PasswordGenerateComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');
    var _ = require('underscore');

    PasswordGenerateComponent = BaseComponent.extend({
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
