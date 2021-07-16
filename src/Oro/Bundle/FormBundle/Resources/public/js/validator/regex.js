define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const defaultParam = {
        message: 'This value is not valid.',
        match: true
    };

    /**
     * @export oroform/js/validator/regex
     */
    return [
        'Regex',
        function(value, element, param) {
            const parts = param.pattern.match(/^\/([\w\W]*?)\/(g?i?m?y?)$/);
            const pattern = new RegExp(parts[1], parts[2]);
            param = Object.assign({}, defaultParam, param);
            return this.optional(element) || Boolean(param.match) === pattern.test(value);
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
