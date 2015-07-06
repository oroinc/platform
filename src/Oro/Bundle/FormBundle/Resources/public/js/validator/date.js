define(['underscore', 'orotranslation/js/translator', 'orolocale/js/formatter/datetime'
    ], function(_, __, datetimeFormatter) {
    'use strict';

    var defaultParam = {
        message: 'This value is not a valid date.'
    };

    /**
     * @export oroform/js/validator/date
     */
    return [
        'Date',
        function(value, element) {
            return this.optional(element) || element.type === 'date' || datetimeFormatter.isDateValid(String(value));
        },
        function(param, element) {
            var value = String(this.elementValue(element)),
                placeholders = {};
            param = _.extend({}, defaultParam, param);
            placeholders.value = value;
            return __(param.message, placeholders);
        }
    ];
});
