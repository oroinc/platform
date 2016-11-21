/* global define */
define([
    'underscore', 'orotranslation/js/translator', 'orouser/js/tools/unicode-matcher'
], function(_, __, unicodeMatcher) {
    'use strict';

    var defaultParam = {
        message: 'Invalid value {{ value }}.',
        requireMinLength: 0,
        requireLowerCase: false,
        requireUpperCase: false,
        requireNumbers: false,
        requireSpecialCharacter: false,
        baseKey: 'oro.user.message.invalid_password.',
        requireMinLengthKey: 'min_length',
        requireLowerCaseKey: 'lower_case',
        requireUpperCaseKey: 'upper_case',
        requireNumbersKey: 'numbers',
        requireSpecialCharacterKey: 'special_chars'
    };

    /**
     * @export orouser/js/validator/password-complexity
     */
    return [
        'Oro\\Bundle\\UserBundle\\Validator\\Constraints\\PasswordComplexity',
        function(value, element, param) {
            var messages = [];

            if (value.length < param.requireMinLength) {
                messages.push(param.requireMinLengthKey);
            }

            if (Boolean(param.requireLowerCase) && !unicodeMatcher.matchLowerCase(value)) {
                messages.push(param.requireLowerCaseKey);
            }

            if (Boolean(param.requireUpperCase) && !unicodeMatcher.matchUpperCase(value)) {
                messages.push(param.requireUpperCaseKey);
            }

            if (Boolean(param.requireNumbers) && !unicodeMatcher.matchNumbers(value)) {
                messages.push(param.requireNumbersKey);
            }

            if (Boolean(param.requireSpecialCharacter) && !unicodeMatcher.matchSpecialChars(value)) {
                messages.push(param.requireSpecialCharacterKey);
            }

            if (messages.length > 0) {
                param.message = param.baseKey + messages.join('_');
            }

            return 0 === messages.length;
        },
        function(param, element) {
            var placeholders = {};

            param = _.extend({}, defaultParam, param);
            placeholders.value = this.elementValue(element);
            placeholders.length = param.requireMinLength;

            return __(param.message, placeholders);
        }
    ];
});
