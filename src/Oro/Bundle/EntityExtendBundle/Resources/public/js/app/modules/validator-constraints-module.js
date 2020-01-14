define(function(require) {
    'use strict';

    const $ = require('jquery.validate');

    $.validator.loadMethod([
        'oroentityextend/js/validator/field-name-length'
    ]);
});
