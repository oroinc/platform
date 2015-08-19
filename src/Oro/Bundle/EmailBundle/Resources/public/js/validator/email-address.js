define([
    'underscore',
    'orotranslation/js/translator',
    'jquery.validate',
], function( _, __) {
    'use strict';

    var defaultParam = {
        message: 'This value is not a valid email address.'
    };

    var emailRegExp = new RegExp(
        '^(([^<>()[\\]\\\\.,;:\\s@\\"]+(\\.[^<>()[\\]\\\\.,;:\\s@\\"]+)*)|' +
        '(\\".+\\"))@(([^<>()[\\]\\\\.,;:\\s@\\"]+(\\.[^<>()[\\]\\\\.,;:\\s@\\"]+)*)|(\\".+\\"))$', 'i');

    function extractPureEmailAddress(fullEmailAddress) {
        var atPos = fullEmailAddress.lastIndexOf('@');
        if (atPos === -1) {
            return fullEmailAddress;
        }

        var startPos = fullEmailAddress.lastIndexOf('<', atPos);
        if (startPos === -1) {
            return fullEmailAddress;
        }

        var endPos = fullEmailAddress.indexOf('>', atPos);
        if (endPos === -1) {
            return fullEmailAddress;
        }

        return fullEmailAddress.substring(startPos + 1, endPos);
    }

    /**
     * @export oroemail/js/validator/email
     */
    return [
        'Oro\\Bundle\\EmailBundle\\Validator\\Constraints\\EmailAddress',
        function(value, element) {
            // @TODO add support of MX check action
            // original email validator is too slow for some values
            // return $.validator.methods.email.apply(this, arguments);
            var $el = $(element);
            var values = $el.data('select2') ? $el.select2('val') : [value];
            return this.optional(element) || _.every(values, function (val) {
                return emailRegExp.test(extractPureEmailAddress(val));
            });
        },
        function(param, element) {
            var value = this.elementValue(element);
            var placeholders = {};
            param = _.extend({}, defaultParam, param);
            placeholders.value = value;
            return __(param.message, placeholders);
        }
    ];
});
