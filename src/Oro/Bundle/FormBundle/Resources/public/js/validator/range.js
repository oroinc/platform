define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var numberFormatter = require('orolocale/js/formatter/number');
    var defaultParam = {
        minMessage: 'This value should be {{ limit }} or more.',
        maxMessage: 'This value should be {{ limit }} or less.',
        invalidMessage: 'This value should be a valid number.'
    };

    /**
     * @export oroform/js/validator/range
     */
    /* TODO: currently this validator support only numeric range. To consistence with backend validator
            it needs to support DateTime range as well
     */
    return [
        'Range',
        function(value, element, param) {
            value = numberFormatter.unformatStrict(value);
            return this.optional(element) ||
                !(isNaN(value) ||
                    (param.min !== null && value < Number(param.min)) ||
                    (param.max !== null && value > Number(param.max)));
        },
        function(param, element) {
            var message;
            var placeholders = {};
            var value = this.elementValue(element);
            var normalizedValue = numberFormatter.unformatStrict(value);
            param = _.extend({}, defaultParam, param);
            if (isNaN(normalizedValue)) {
                message = param.invalidMessage;
            } else if (param.min !== null && normalizedValue < Number(param.min)) {
                message = param.minMessage;
                placeholders.limit = param.min;
            } else if (param.max !== null && normalizedValue > Number(param.max)) {
                message = param.maxMessage;
                placeholders.limit = param.max;
            }
            placeholders.value = value;
            return __(message, placeholders);
        }
    ];
});
