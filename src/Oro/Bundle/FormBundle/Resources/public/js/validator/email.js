/*global define*/
define(['jquery', 'underscore', 'orotranslation/js/translator', 'jquery.validate'
    ], function ($, _, __) {
    'use strict';

    var defaultParam = {
        message: 'This value is not a valid email address.'
    };

    /**
     * @export oroform/js/validator/email
     */
    return [
        'Email',
        function (value, element) {
            // @TODO add support of MX check action
            // original email validator is too slow for some values
            //  return $.validator.methods.email.apply(this, arguments);
            return this.optional(element) || /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))$/i.test(value)
        },
        function (param, element) {
            var value = this.elementValue(element),
                placeholders = {};
            param = _.extend({}, defaultParam, param);
            placeholders.value = value;
            return __(param.message, placeholders);
        }
    ];
});
