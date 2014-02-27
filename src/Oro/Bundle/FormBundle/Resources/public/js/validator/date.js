/*global define*/
define(['underscore', 'orotranslation/js/translator', 'orolocale/js/formatter/datetime'
    ], function (_, __, datetimeFormatter) {
    'use strict';

    var defaultParam = {
        message: 'This value is not a valid date.'
    };

    /**
     * @export oroform/js/validator/date
     */
    return [
        'Date',
        function (value, element) {
            return this.optional(element) || datetimeFormatter.isDateValid(String(value));
        },
        function (param) {
            param = _.extend({}, defaultParam, param);
            return __(param.message);
        }
    ];
});
