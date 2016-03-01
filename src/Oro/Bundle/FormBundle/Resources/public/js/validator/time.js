define(['underscore', 'orotranslation/js/translator', 'orolocale/js/formatter/datetime'
    ], function(_, __, datetimeFormatter) {
    'use strict';

    var defaultParam = {
        message: 'This value is not a valid time.'
    };

    /**
     * @export oroform/js/validator/datetime
     */
    return [
        'Time',
        function(value, element) {
            var format = element.getAttribute('data-format');
            return this.optional(element) || element.type === 'time' ||
                datetimeFormatter.isTimeValid(String(value)) ||
                format === 'backend' && datetimeFormatter.isBackendTimeValid(String(value));
        },
        function(param, element) {
            var value = String(this.elementValue(element));
            var placeholders = {};
            param = _.extend({}, defaultParam, param);
            placeholders.value = value;
            return __(param.message, placeholders);
        }
    ];
});
