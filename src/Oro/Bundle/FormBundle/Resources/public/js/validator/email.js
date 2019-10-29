define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const emailRegExp = require('oroui/js/tools/patterns').email;
    require('jquery.validate');

    const defaultParam = {
        message: 'This value is not a valid email address.'
    };

    /**
     * @export oroform/js/validator/email
     */
    return [
        'Email',
        function(value, element) {
            // @TODO add support of MX check action
            // original email validator is too slow for some values
            // return $.validator.methods.email.apply(this, arguments);
            return this.optional(element) || emailRegExp.test(value);
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
