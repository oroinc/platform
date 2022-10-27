define(function(require) {
    'use strict';

    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');
    require('jquery.validate');

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
            param = Object.assign({}, defaultParam, param);
            return __(param.message);
        }
    ];
});
