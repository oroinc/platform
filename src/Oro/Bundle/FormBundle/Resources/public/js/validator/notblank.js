define(['jquery', 'underscore', 'orotranslation/js/translator', 'jquery.validate'
], function($, _, __) {
    'use strict';

    const defaultParam = {
        message: 'This value should not be blank.'
    };

    /**
     * @export oroform/js/validator/notblank
     */
    return [
        'NotBlank',
        function(...args) {
            return $.validator.methods.required.apply(this, args);
        },
        function(param) {
            param = _.extend({}, defaultParam, param);
            return __(param.message);
        }
    ];
});
