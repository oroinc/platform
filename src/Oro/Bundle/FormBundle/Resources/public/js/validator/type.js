define(['underscore', 'orotranslation/js/translator'
], function(_, __) {
    'use strict';

    var defaultParam = {
        message: 'This value should be of type {{ type }}.'
    };

    /**
     * @export oroform/js/validator/type
     */
    return [
        'Type',
        function(value, element, param) {
            var result;
            switch (param.type) {
                case 'integer':
                    var valueNumber = Number(value).toFixed();
                    result = String(valueNumber) === value;
                    break;
                default:
                    result = true;
            }
            return this.optional(element) || result;
        },
        function(param) {
            param = _.extend({}, defaultParam, param);
            return __(param.message, {type: param.type});
        }
    ];
});
