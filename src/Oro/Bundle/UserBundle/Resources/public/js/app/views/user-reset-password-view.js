define(function(require) {
    'use strict';

    var UserResetPasswordView;
    var BaseView = require('oroui/js/app/views/base/view');
    var __ = require('orotranslation/js/translator');
    var _ = require('underscore');

    UserResetPasswordView = BaseView.extend({
        autoRender: true,

        optionNames: BaseView.prototype.optionNames.concat(['passwordInputSelector']),

        events: {
            'click [data-role="generate-pass"]': 'onGeneratePassButtonClick',
            'click [data-role="show-hide-pass"]': 'onShowHideButtonClick'
        },

        defaults: {
            passwordMinLength: 1
        },

        passwordShowHideTemplate: require('tpl!orouser/templates/user-reset-password-show-hide.html'),

        passwordSuggestionTemplate: require('tpl!orouser/templates/user-reset-password-suggestion.html'),

        charsets: {
            lower_case: 'abcdefghijklmnopqrstuvwxyz',
            upper_case: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            numbers: '0123456789',
            special_chars: ' !"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~'
        },

        /**
         * @inheritDoc
         */
        constructor: function UserResetPasswordView(options) {
            UserResetPasswordView.__super__.constructor.call(this, options);
        },

        render: function() {
            var $passwordInput = this._getPasswordInput();

            $passwordInput.after(this.passwordShowHideTemplate({
                title: __('oro.user.show_hide_password.label')
            }));

            $passwordInput.after(this.passwordSuggestionTemplate({
                label: __('oro.user.suggest_password.label')
            }));

            return UserResetPasswordView.__super__.render.apply(this, arguments);
        },

        onShowHideButtonClick: function(e) {
            var $passwordInput = this._getPasswordInput();
            var $target = this.$(e.target);

            if ($target.hasClass('fa-eye')) {
                $passwordInput.attr('type', 'password');
                $target.removeClass('fa-eye');
                $target.addClass('fa-eye-slash');
            } else {
                $passwordInput.attr('type', 'text');
                $target.removeClass('fa-eye-slash');
                $target.addClass('fa-eye');
            }
        },

        onGeneratePassButtonClick: function(e) {
            e.preventDefault();

            this._getPasswordInput().val(this._generatePassword());
        },

        _generatePassword: function() {
            var length = this._getRequiredPasswordLength();
            var rules = this._getPasswordRequirements();
            var pass = '';

            // make sure we have at least one symbol for each rule, shuffle them later
            rules.forEach(function(rule) {
                if (this.charsets.hasOwnProperty(rule)) {
                    pass += this.charsets[rule].charAt(_.random(this.charsets[rule].length - 1));
                }
            }.bind(this));

            // create a pool for picking random chars that is reasonably strong
            var charset = this.charsets.lower_case + this.charsets.upper_case + this.charsets.numbers;

            // fill up to the minLength with random symbols
            for (var i = pass.length; i < length; ++i) {
                pass = pass + charset.charAt(_.random(charset.length - 1));
            }

            // shuffle the password
            pass = pass.split('').sort(function() {
                return 0.5 - Math.random();
            }).join('');

            return pass;
        },

        _getPasswordInput: function() {
            return this.$(this.passwordInputSelector);
        },

        _getRequiredPasswordLength: function() {
            return this._getPasswordInput().data('suggest-length') || this.defaults.passwordMinLength;
        },

        _getPasswordRequirements: function() {
            var rules = this._getPasswordInput().data('suggest-rules');

            return rules ? rules.split(',') : [];
        }
    });

    return UserResetPasswordView;
});
