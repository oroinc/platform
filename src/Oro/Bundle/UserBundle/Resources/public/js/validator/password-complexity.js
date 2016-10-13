/* global define */
define(['underscore', 'orotranslation/js/translator'
], function(_, __) {
    'use strict';

    var defaultParam = {
        message: 'Invalid value {{ value }}.',
        requireMinLength: 0,
        requireUpperCase: false,
        requireNumbers: false,
        requireSpecialCharacter: false,
        baseKey: 'oro.user.message.invalid_password.',
        requireMinLengthKey: 'min_length',
        requireUpperCaseKey: 'upper_case',
        requireNumbersKey: 'numbers',
        requireSpecialCharacterKey: 'special_chars'
    };

    /**
     * @export oroform/js/validator/password-complexity
     */
    return [
        'Oro\\Bundle\\UserBundle\\Validator\\Constraints\\PasswordComplexity',
        function(value, element, param) {
            var messages = [];

            if (value.length < param.requireMinLength) {
                messages.push(param.requireMinLengthKey);
            }

            if (Boolean(param.requireUpperCase) && !value.match(/[A-Z]/)) {
                messages.push(param.requireUpperCaseKey);
            }

            if (Boolean(param.requireNumbers) && !value.match(/\d/)) {
                messages.push(param.requireNumbersKey);
            }

            if (Boolean(param.requireSpecialCharacter) && !value.match(/[\W_]/)) {
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
