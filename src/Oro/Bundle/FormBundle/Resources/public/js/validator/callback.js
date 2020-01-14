define(['underscore', 'orotranslation/js/translator'], function(_, __) {
    'use strict';

    const defaultParam = {
        message: 'This value is not valid.',
        callback: null
    };

    /**
     * @export oroform/js/validator/callback
     */
    return [
        'Callback',
        function(value, element, param) {
            if (!_.isFunction(param.callback)) {
                throw new Error('Validation[Callback method]: "callback" param is not a function');
            }
            return param.callback(value, element, param);
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
