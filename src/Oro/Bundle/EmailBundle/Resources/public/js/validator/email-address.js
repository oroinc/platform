define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const emailRegExp = require('oroui/js/tools/patterns').email;
    const emailUtil = require('oroemail/js/util/email');
    require('jquery.validate');

    const defaultParam = {
        message: 'This value is not a valid email address.'
    };

    /**
     * @export oroemail/js/validator/email
     */
    return [
        'Oro\\Bundle\\EmailBundle\\Validator\\Constraints\\EmailAddress',
        function(value, element) {
            const $el = $(element);

            if ($el.parent().is(':hidden')) {
                return true;
            }

            if (_.isEmpty(value)) {
                return false;
            }

            // @TODO add support of MX check action
            // original email validator is too slow for some values
            // return $.validator.methods.email.apply(this, arguments);

            let values = null;
            if ($el.data('select2')) {
                const data = $el.inputWidget('data');
                const arrayData = _.isArray(data) ? data : [data];
                values = _.map(arrayData, _.partial(_.result, _, 'text'));
            } else {
                values = [value];
            }

            return this.optional(element) || _.every(values, function(val) {
                return emailRegExp.test(emailUtil.extractPureEmailAddress(val));
            });
        },
        function(param, element) {
            const value = this.elementValue(element);
            const placeholders = {};
            param = _.extend({}, defaultParam, param);
            placeholders.value = value;
            return __(param.message, placeholders);
        }
    ];
});
