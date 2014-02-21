/*global define*/
define(['jquery', 'underscore', 'oro/translator', 'jquery.validate'
    ], function ($, _, __) {
    'use strict';

    var defaultParam = {
        message: 'This value should not be blank.'
    };

    /**
     * @export oroform/js/validator/notblank
     */
    return [
        'NotBlank',
        function () {
            return $.validator.methods.required.apply(this, arguments);
        },
        function (param) {
            param = _.extend({}, defaultParam, param);
            return __(param.message);
        }
    ];
});
