define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const {email_html5: regExpHTML5, email_loose: regExpLoose} = require('oroui/js/tools/patterns');
    require('jquery.validate');

    const defaultParam = {
        message: 'This value is not a valid email address.'
    };

    /**
     * @export oroform/js/validator/email
     */
    return [
        'Email',
        function(value, element, param) {
            const {mode, normalizer = null} = param || {};
            // only `html5` and `loose` modes are supported, any other mode is treated as `loose`
            const regExp = mode === 'html5' && !normalizer ? regExpHTML5 : regExpLoose;
            return this.optional(element) || regExp.test(value);
        },
        function(param, element) {
            const value = this.elementValue(element);
            const placeholders = {};
            param = Object.assign({}, defaultParam, param);
            placeholders.value = value;
            return __(param.message, placeholders);
        }
    ];
});
