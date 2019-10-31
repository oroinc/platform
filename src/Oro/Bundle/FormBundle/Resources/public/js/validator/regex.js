define(['underscore', 'orotranslation/js/translator'
], function(_, __) {
    'use strict';

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
            param = _.extend({}, defaultParam, param);
            return this.optional(element) || Boolean(param.match) === pattern.test(value);
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
