define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var numberFormatter = require('orolocale/js/formatter/number');
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
                case 'float':
                case 'numeric':
                    result = !isNaN(numberFormatter.unformatStrict(value));
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
