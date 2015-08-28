define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroemail/js/util/email',
    'jquery.validate'
], function($, _, __, emailUtil) {
    'use strict';

    var defaultParam = {
        message: 'This value is not a valid email address.'
    };

    var emailRegExp = new RegExp(
        '^(([^<>()[\\]\\\\.,;:\\s@\\"]+(\\.[^<>()[\\]\\\\.,;:\\s@\\"]+)*)|' +
        '(\\".+\\"))@(([^<>()[\\]\\\\.,;:\\s@\\"]+(\\.[^<>()[\\]\\\\.,;:\\s@\\"]+)*)|(\\".+\\"))$', 'i');

    /**
     * @export oroemail/js/validator/email
     */
    return [
        'Oro\\Bundle\\EmailBundle\\Validator\\Constraints\\EmailAddress',
        function(value, element) {
            if (_.isEmpty(value)) {
                return true;
            }

            // @TODO add support of MX check action
            // original email validator is too slow for some values
            // return $.validator.methods.email.apply(this, arguments);
            var $el = $(element);

            var values = null;
            if ($el.data('select2')) {
                var data = $el.select2('data');
                var arrayData = _.isArray(data) ? data : [data];
                values = _.map(arrayData, _.partial(_.result, _, 'text'));
            } else {
                values = [value];
            }

            return this.optional(element) || _.every(values, function(val) {
                return emailRegExp.test(emailUtil.extractPureEmailAddress(val));
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
