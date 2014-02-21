/*global define*/
define(['underscore', 'oro/translator'
    ], function (_, __) {
    'use strict';

    var defaultParam = {
        message: 'This value is not valid.',
        match: true
    };

    /**
     * @export oroform/js/validator/regex
     */
    return [
        'Regex',
        function (value, element, param) {
            var parts = param.pattern.match(/^\/(\w\W*?)\/(g?i?m?y?)$/),
                pattern = new RegExp(parts[1], parts[2]);
            param = _.extend({}, defaultParam, param);
            return this.optional(element) || Boolean(param.match) === pattern.test(value);
        },
        function (param) {
            param = _.extend({}, defaultParam, param);
            return __(param.message);
        }
    ];
});
