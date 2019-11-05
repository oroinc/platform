define(function(require) {
    'use strict';

    const $ = require('jquery.validate');

    $.validator.loadMethod('orouser/js/validator/password-complexity');
});
