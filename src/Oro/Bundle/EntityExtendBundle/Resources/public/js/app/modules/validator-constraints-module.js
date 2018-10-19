define(function(require) {
    'use strict';

    var $ = require('jquery.validate');

    $.validator.loadMethod([
        'oroentityextend/js/validator/field-name-length'
    ]);
});
