define(function(require) {
    'use strict';

    require('jasmine-jquery');
    var $ = require('jquery');
    var UserResetPasswordView = require('orouser/js/app/views/user-reset-password-view');

    describe('User Reset Password View', function() {
        function createUserResetPasswordView() {
            return new UserResetPasswordView({
                el: '#form',
                passwordInputSelector: '.password-field'
            });
        }

        beforeEach(function() {
            window.setFixtures(
                '<form id="form">' +
                    '<input class="password-field"/>' +
                '</form>'
            );
        });

        it('_getPasswordInput - when selector is passed', function() {
            expect(createUserResetPasswordView()._getPasswordInput().hasClass('password-field')).toBeTruthy();
        });

        it('_getPasswordRequirements - when are set in backend', function() {
            $('.password-field').data({
                'suggest-rules': 'rule_1,rule_2,rule_3'
            });

            expect(createUserResetPasswordView()._getPasswordRequirements().length).toEqual(3);
        });

        it('_getPasswordRequirements - when are not set in backend', function() {
            expect(createUserResetPasswordView()._getPasswordRequirements().length).toEqual(0);
        });

        it('_getRequiredPasswordLength - when are set in backend', function() {
            $('.password-field').data({
                'suggest-length': 5
            });

            expect(createUserResetPasswordView()._getRequiredPasswordLength()).toEqual(5);
        });

        it('_getRequiredPasswordLength - when are not set in backend', function() {
            expect(createUserResetPasswordView()._getRequiredPasswordLength()).toEqual(1);
        });

        it('_generatePassword', function() {
            var passwordLength = 15;

            $('.password-field').data({
                'suggest-length': passwordLength,
                'suggest-rules': 'lower_case,upper_case,numbers'
            });

            expect(createUserResetPasswordView()._generatePassword().length).toEqual(passwordLength);
        });
    });
});
