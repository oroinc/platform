define(['jquery', 'underscore', 'orotranslation/js/translator', 'orolocale/js/formatter/number'
], function($, _, __, numberFormatter) {
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
            switch (param.type) {
                case 'integer':
                    var valueNumber = ~~Number(value);
                    return String(valueNumber) === value;
                default:
                    return true;
            }
        },
        function(param) {
            param = _.extend({}, defaultParam, param);
            return __(param.message, { type: param.type });
        }
    ];
});
