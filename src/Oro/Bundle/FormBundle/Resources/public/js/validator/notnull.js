define(['jquery', 'underscore', 'orotranslation/js/translator', 'jquery.validate'
], function($, _, __) {
    'use strict';

    const defaultParam = {
        message: 'This value should not be null.'
    };

    /**
     * @export oroform/js/validator/notnull
     */
    return [
        'NotNull',
        function(...args) {
            return $.validator.methods.required.apply(this, args);
        },
        function(param) {
            param = _.extend({}, defaultParam, param);
            return __(param.message);
        }
    ];
});
